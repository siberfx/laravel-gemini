<?php

declare(strict_types=1);

namespace Siberfx\LaravelGemini\Tests;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Siberfx\LaravelGemini\Exceptions\ValidationException;
use Siberfx\LaravelGemini\Facades\Gemini;

class FilesAndCachesTest extends TestCase
{
    #[Test]
    public function it_uploads_a_file_with_the_resumable_protocol(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'gem').'.pdf';
        file_put_contents($path, '%PDF-1.4');

        Http::fake([
            '*/upload/v1beta/files' => Http::response([], 200, ['X-Goog-Upload-URL' => 'https://upload.test/session/1']),
            'upload.test/*' => Http::response(['file' => ['uri' => 'https://generativelanguage.googleapis.com/v1beta/files/f1']]),
        ]);

        $uri = Gemini::files()->upload('document', $path);
        @unlink($path);

        $this->assertSame('https://generativelanguage.googleapis.com/v1beta/files/f1', $uri);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/upload/v1beta/files')
            && $request->header('X-Goog-Upload-Protocol') === ['resumable']
            && $request->header('X-Goog-Upload-Command') === ['start']
            && $request->header('X-Goog-Upload-Header-Content-Length') === ['8']
            && $request->header('X-Goog-Upload-Header-Content-Type') === ['application/pdf']);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://upload.test/session/1'
            && $request->header('X-Goog-Upload-Command') === ['upload, finalize']
            && $request->body() === '%PDF-1.4'
            && ! $request->hasHeader('X-Goog-Upload-Protocol'));
    }

    #[Test]
    public function it_rejects_unsupported_uploads(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'gem').'.exe';
        file_put_contents($path, 'x');

        try {
            $this->expectException(ValidationException::class);
            Gemini::files()->upload('document', $path);
        } finally {
            @unlink($path);
        }
    }

    #[Test]
    public function it_manages_files_by_short_or_full_name(): void
    {
        Http::fake([
            '*/v1beta/files/f1' => Http::response(['name' => 'files/f1', 'uri' => 'u', 'state' => 'ACTIVE']),
            '*/v1beta/files*' => Http::response(['files' => [['name' => 'files/f1']], 'nextPageToken' => 'next']),
        ]);

        $this->assertSame('ACTIVE', Gemini::files()->get('f1')->state());
        $this->assertTrue(Gemini::files()->delete('files/f1'));

        $list = Gemini::files()->list(['pageSize' => 10]);
        $this->assertCount(1, $list->files());
        $this->assertSame('next', $list->nextPageToken());
    }

    #[Test]
    public function it_manages_cached_contents(): void
    {
        Http::fake([
            '*/v1beta/cachedContents' => Http::response(['name' => 'cachedContents/c1']),
            '*/v1beta/cachedContents?*' => Http::response(['cachedContents' => [['name' => 'cachedContents/c1']], 'nextPageToken' => 'n']),
            '*/v1beta/cachedContents/c1' => Http::response(['name' => 'cachedContents/c1', 'expireTime' => '2030-01-01T00:00:00Z']),
        ]);

        $created = Gemini::caches()->create(
            model: 'gemini-2.5-flash',
            contents: [['role' => 'user', 'parts' => [['text' => 'ctx']]]],
            systemInstruction: 'sys',
            ttl: '600s',
        );
        $this->assertSame('cachedContents/c1', $created->name());

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request['model'] === 'models/gemini-2.5-flash'
            && $request['ttl'] === '600s'
            && $request['systemInstruction'] === ['parts' => [['text' => 'sys']]]
            && ! isset($request['tools']));

        $this->assertSame('n', Gemini::caches()->list()->nextPageToken());
        $this->assertSame('2030-01-01T00:00:00Z', Gemini::caches()->update('c1', ttl: '60s')->expireTime());
        $this->assertTrue(Gemini::caches()->delete('cachedContents/c1'));

        Http::assertSent(fn (Request $request): bool => $request->method() === 'PATCH' && $request->data() === ['ttl' => '60s']);
    }

    #[Test]
    public function builders_can_create_a_cache_from_their_state(): void
    {
        Http::fake(['*' => Http::response(['name' => 'cachedContents/c9'])]);

        $name = Gemini::text()->system('sys')->prompt('long context')->cache(displayName: 'Docs');

        $this->assertSame('cachedContents/c9', $name);
        Http::assertSent(fn (Request $request): bool => $request['displayName'] === 'Docs'
            && $request['ttl'] === '3600s'
            && $request['contents'] === [['role' => 'user', 'parts' => [['text' => 'long context']]]]);
    }

    #[Test]
    public function cache_update_requires_an_expiration(): void
    {
        $this->expectException(ValidationException::class);

        Gemini::caches()->update('c1');
    }
}
