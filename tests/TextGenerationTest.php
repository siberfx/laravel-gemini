<?php

declare(strict_types=1);

namespace Siberfx\LaravelGemini\Tests;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Siberfx\LaravelGemini\Enums\GenerationMethod;
use Siberfx\LaravelGemini\Exceptions\ApiException;
use Siberfx\LaravelGemini\Exceptions\ValidationException;
use Siberfx\LaravelGemini\Facades\Gemini;

class TextGenerationTest extends TestCase
{
    #[Test]
    public function it_generates_text_and_concatenates_non_thought_parts(): void
    {
        Http::fake(['*' => Http::response([
            ...$this->candidate([['text' => 'thinking…', 'thought' => true], ['text' => 'Hel'], ['text' => 'lo']]),
            'modelVersion' => 'gemini-2.5-flash-lite',
            'responseId' => 'r-1',
        ])]);

        $response = Gemini::text()->prompt('Hi')->generate();

        $this->assertSame('Hello', $response->content());
        $this->assertSame('gemini-2.5-flash-lite', $response->model());
        $this->assertSame('r-1', $response->requestId());
        $this->assertSame('STOP', $response->finishReason());
    }

    #[Test]
    public function it_builds_the_request_body(): void
    {
        Http::fake(['*' => Http::response($this->candidate([['text' => 'ok']]))]);

        Gemini::text()
            ->model('gemini-2.5-flash')
            ->system('Be brief.')
            ->history([['role' => 'model', 'parts' => [['text' => 'Earlier']]]])
            ->prompt('Now')
            ->temperature(0.2)
            ->maxTokens(50)
            ->functionCalls([['name' => 'lookup']])
            ->cachedContent('cachedContents/abc')
            ->generate();

        Http::assertSent(function (Request $request): bool {
            $body = $request->data();

            return $request->url() === 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent'
                && $request->header('x-goog-api-key') === ['test-key']
                && $body['contents'] === [
                    ['role' => 'model', 'parts' => [['text' => 'Earlier']]],
                    ['role' => 'user', 'parts' => [['text' => 'Now']]],
                ]
                && $body['systemInstruction'] === ['parts' => [['text' => 'Be brief.']]]
                && $body['generationConfig'] === ['temperature' => 0.2, 'maxOutputTokens' => 50]
                && $body['tools'] === [['functionDeclarations' => [['name' => 'lookup']]]]
                && $body['cachedContent'] === 'cachedContents/abc'
                && $body['safetySettings'] === config('gemini.safety_settings');
        });
    }

    #[Test]
    public function it_omits_generation_config_when_nothing_is_set(): void
    {
        Http::fake(['*' => Http::response($this->candidate([['text' => 'ok']]))]);

        Gemini::text()->prompt('Hi')->generate();

        Http::assertSent(fn (Request $request): bool => ! array_key_exists('generationConfig', $request->data()));
    }

    #[Test]
    public function it_decodes_structured_output(): void
    {
        Http::fake(['*' => Http::response($this->candidate([['text' => '{"name":"Ada","age":36}']]))]);

        $response = Gemini::text()
            ->structuredSchema(['type' => 'object'])
            ->prompt('Who?')
            ->generate();

        $this->assertSame(['name' => 'Ada', 'age' => 36], $response->decode());
        Http::assertSent(fn (Request $request): bool => $request['generationConfig']['responseMimeType'] === 'application/json');
    }

    #[Test]
    public function it_exposes_function_calls(): void
    {
        Http::fake(['*' => Http::response($this->candidate([
            ['functionCall' => ['name' => 'lookup', 'args' => ['q' => 'x']]],
        ]))]);

        $calls = Gemini::text()->prompt('Find x')->functionCalls([['name' => 'lookup']])->generate()->functionCalls();

        $this->assertSame([['name' => 'lookup', 'args' => ['q' => 'x']]], $calls);
    }

    #[Test]
    public function it_attaches_a_pre_uploaded_file(): void
    {
        Http::fake(['*' => Http::response($this->candidate([['text' => 'ok']]))]);

        Gemini::text()->file('video/mp4', 'https://files/abc')->prompt('Describe')->generate();

        Http::assertSent(fn (Request $request): bool => $request['contents'][0]['parts'][1]
            === ['fileData' => ['mimeType' => 'video/mp4', 'fileUri' => 'https://files/abc']]);
    }

    #[Test]
    public function it_inlines_local_images(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'gem').'.png';
        file_put_contents($path, 'PNGDATA');

        Http::fake(['*' => Http::response($this->candidate([['text' => 'ok']]))]);

        Gemini::text()->upload('image', $path)->prompt('What is this?')->generate();

        Http::assertSent(fn (Request $request): bool => $request['contents'][0]['parts'][1]
            === ['inlineData' => ['mimeType' => 'image/png', 'data' => base64_encode('PNGDATA')]]);

        @unlink($path);
    }

    #[Test]
    public function it_throws_when_finish_reason_is_not_stop(): void
    {
        Http::fake(['*' => Http::response($this->candidate([], 'SAFETY'))]);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('SAFETY');

        Gemini::text()->prompt('Hi')->generate();
    }

    #[Test]
    public function it_requires_a_prompt(): void
    {
        $this->expectException(ValidationException::class);

        Gemini::text()->generate();
    }

    #[Test]
    public function it_validates_the_method(): void
    {
        $builder = Gemini::text()->method(GenerationMethod::PREDICT);
        $this->assertSame('predict', $builder->toArray()['method']);

        $this->expectException(ValidationException::class);
        Gemini::text()->method('nope');
    }

    #[Test]
    public function it_streams_parts_over_sse(): void
    {
        Http::fake(['*' => Http::response(
            "data: {\"candidates\":[{\"content\":{\"parts\":[{\"text\":\"A\"}]}}]}\r\n\r\n"
            .'data: {"candidates":[{"content":{"parts":[{"text":"B"},{"text":"C"}]}}]}'
        )]);

        $received = '';
        Gemini::text()->prompt('Go')->stream(function (array $part) use (&$received): void {
            $received .= $part['text'];
        });

        $this->assertSame('ABC', $received);
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), ':streamGenerateContent?alt=sse'));
    }

    #[Test]
    public function it_uses_a_runtime_api_key(): void
    {
        Http::fake(['*' => Http::response($this->candidate([['text' => 'ok']]))]);

        Gemini::setApiKey('runtime-key')->text()->prompt('Hi')->generate();

        Http::assertSent(fn (Request $request): bool => $request->header('x-goog-api-key') === ['runtime-key']);
    }
}
