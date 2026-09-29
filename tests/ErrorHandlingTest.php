<?php

declare(strict_types=1);

namespace Siberfx\LaravelGemini\Tests;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Siberfx\LaravelGemini\Exceptions\ApiException;
use Siberfx\LaravelGemini\Exceptions\AuthenticationException;
use Siberfx\LaravelGemini\Exceptions\NetworkException;
use Siberfx\LaravelGemini\Exceptions\RateLimitException;
use Siberfx\LaravelGemini\Exceptions\ValidationException;
use Siberfx\LaravelGemini\Facades\Gemini;
use Siberfx\LaravelGemini\Http\HttpClient;

class ErrorHandlingTest extends TestCase
{
    public static function statusProvider(): array
    {
        return [
            '400' => [400, ValidationException::class],
            '401' => [401, AuthenticationException::class],
            '403' => [403, AuthenticationException::class],
            '404' => [404, NetworkException::class],
            '500' => [500, ApiException::class],
        ];
    }

    #[Test]
    #[DataProvider('statusProvider')]
    public function it_maps_status_codes_to_typed_exceptions(int $status, string $exception): void
    {
        Http::fake(['*' => Http::response(['error' => ['message' => 'API says no']], $status)]);

        $this->expectException($exception);
        $this->expectExceptionMessage('API says no');

        Gemini::text()->prompt('Hi')->generate();
    }

    #[Test]
    public function it_retries_rate_limits_honouring_retry_after(): void
    {
        Http::fake(['*' => Http::response([], 429, ['Retry-After' => '7'])]);

        try {
            Gemini::text()->prompt('Hi')->generate();
            $this->fail('Expected RateLimitException');
        } catch (RateLimitException $e) {
            $this->assertSame(7, $e->retryAfter);
        }

        Http::assertSentCount(3);
        Sleep::assertSlept(fn ($duration): bool => (int) $duration->totalMilliseconds === 7000, 2);
    }

    #[Test]
    public function it_retries_server_errors_then_succeeds(): void
    {
        Http::fake(['*' => Http::sequence()
            ->push([], 503)
            ->push($this->candidate([['text' => 'recovered']]))]);

        $this->assertSame('recovered', Gemini::text()->prompt('Hi')->generate()->content());
        Http::assertSentCount(2);
    }

    #[Test]
    public function it_does_not_retry_client_errors(): void
    {
        Http::fake(['*' => Http::response([], 400)]);

        try {
            Gemini::text()->prompt('Hi')->generate();
        } catch (ValidationException) {
        }

        Http::assertSentCount(1);
    }

    #[Test]
    public function http_client_headers_do_not_leak_between_requests(): void
    {
        Http::fake();

        $client = new HttpClient('https://example.test', 'k');
        $client->withHeaders(['X-Once' => '1'])->get('/a');
        $client->get('/b');

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/a') && $request->hasHeader('X-Once'));
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/b') && ! $request->hasHeader('X-Once'));
    }
}
