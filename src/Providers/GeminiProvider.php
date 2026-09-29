<?php

declare(strict_types=1);

namespace Siberfx\LaravelGemini\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use Siberfx\LaravelGemini\Contracts\ProviderInterface;
use Siberfx\LaravelGemini\Enums\GenerationMethod;
use Siberfx\LaravelGemini\Exceptions\ApiException;
use Siberfx\LaravelGemini\Exceptions\StreamException;
use Siberfx\LaravelGemini\Exceptions\ValidationException;
use Siberfx\LaravelGemini\Responses\AudioResponse;
use Siberfx\LaravelGemini\Responses\BaseResponse;
use Siberfx\LaravelGemini\Responses\CacheResponse;
use Siberfx\LaravelGemini\Responses\FileResponse;
use Siberfx\LaravelGemini\Responses\ImageResponse;
use Siberfx\LaravelGemini\Responses\TextResponse;
use Siberfx\LaravelGemini\Responses\VideoResponse;
use Throwable;

class GeminiProvider extends BaseProvider implements ProviderInterface
{
    protected const string API_VERSION = '/v1beta';

    public function generateText(array $params): TextResponse
    {
        return $this->executeRequest($params, TextResponse::class);
    }

    public function generateImage(array $params): ImageResponse
    {
        return $this->executeRequest($params, ImageResponse::class);
    }

    public function generateVideo(array $params): VideoResponse
    {
        return $this->executeRequest($params, VideoResponse::class);
    }

    public function generateAudio(array $params): AudioResponse
    {
        return $this->executeRequest($params, AudioResponse::class, forAudio: true);
    }

    public function embeddings(array $params): array
    {
        $model = $params['model'] ?? $this->config['models']['embedding'] ?? 'gemini-embedding-001';
        $model = str_starts_with($model, 'models/') ? substr($model, 7) : $model;

        $response = $this->http->post(
            self::API_VERSION."/models/{$model}:embedContent",
            [...$params, 'model' => "models/{$model}"],
        );

        $this->ensureSuccessful($response);

        return $response->json();
    }

    public function uploadFile(array $params): string
    {
        if (! isset($params['fileType'], $params['filePath'])) {
            throw new ValidationException('File type and path are required.');
        }

        return $this->upload($params['fileType'], $params['filePath']);
    }

    public function listFiles(array $params = []): FileResponse
    {
        $query = array_filter([
            'pageSize' => $params['pageSize'] ?? null,
            'pageToken' => $params['pageToken'] ?? null,
        ]);

        return $this->handleResponse($this->http->get(self::API_VERSION.'/files', $query), FileResponse::class);
    }

    public function getFile(string $fileName): FileResponse
    {
        return $this->handleResponse(
            $this->http->get(self::API_VERSION.'/'.$this->resourceName('files', $fileName)),
            FileResponse::class,
        );
    }

    public function deleteFile(string $fileName): bool
    {
        return $this->http->delete(self::API_VERSION.'/'.$this->resourceName('files', $fileName))->successful();
    }

    public function createCachedContent(array $params): CacheResponse
    {
        $payload = array_filter([
            'model' => "models/{$params['model']}",
            'contents' => $params['contents'],
            'systemInstruction' => filled($params['systemInstruction'] ?? null)
                ? ['parts' => [['text' => $params['systemInstruction']]]]
                : null,
            'tools' => $params['tools'] ?? null,
            'toolConfig' => $params['toolConfig'] ?? null,
            'displayName' => $params['displayName'] ?? null,
            ...$this->expiration($params),
        ], filled(...));

        return $this->handleResponse(
            $this->http->post(self::API_VERSION.'/cachedContents', $payload),
            CacheResponse::class,
        );
    }

    public function listCachedContents(array $params = []): CacheResponse
    {
        $query = array_filter([
            'pageSize' => $params['pageSize'] ?? config('gemini.caching.max_page_size'),
            'pageToken' => $params['pageToken'] ?? null,
        ]);

        return $this->handleResponse(
            $this->http->get(self::API_VERSION.'/cachedContents', $query),
            CacheResponse::class,
        );
    }

    public function getCachedContent(string $name): CacheResponse
    {
        return $this->handleResponse(
            $this->http->get(self::API_VERSION.'/'.$this->resourceName('cachedContents', $name)),
            CacheResponse::class,
        );
    }

    public function updateCachedContent(string $name, array $expiration): CacheResponse
    {
        $payload = $this->expiration($expiration, withDefault: false)
            ?: throw new ValidationException('TTL or expireTime is required for update.');

        return $this->handleResponse(
            $this->http->patch(self::API_VERSION.'/'.$this->resourceName('cachedContents', $name), $payload),
            CacheResponse::class,
        );
    }

    public function deleteCachedContent(string $name): bool
    {
        return $this->http->delete(self::API_VERSION.'/'.$this->resourceName('cachedContents', $name))->successful();
    }

    public function models(): array
    {
        $response = $this->http->get(self::API_VERSION.'/models', ['pageSize' => 1000]);

        $this->ensureSuccessful($response);

        return $response->json('models', []);
    }

    /**
     * Stream a generateContent request over SSE, invoking the callback for every returned part.
     *
     * @param  callable(array $part): void  $callback
     */
    public function streaming(array $params, callable $callback): void
    {
        if ($this->method($params) !== GenerationMethod::GENERATE_CONTENT) {
            throw new ValidationException('Streaming only supported for generateContent method.');
        }

        $response = $this->http
            ->withOptions(['stream' => true])
            ->post(
                self::API_VERSION."/models/{$params['model']}:streamGenerateContent?alt=sse",
                $this->buildRequestBody($params),
            );

        $this->ensureSuccessful($response);

        try {
            $body = $response->toPsrResponse()->getBody();
            $chunkSize = (int) config('gemini.stream.chunk_size', 1024);
            $buffer = '';

            while (! $body->eof()) {
                $buffer .= $body->read($chunkSize);
                $lines = explode("\n", $buffer);
                $buffer = array_pop($lines);

                foreach ($lines as $line) {
                    $this->emitStreamLine($line, $callback);
                }
            }

            $this->emitStreamLine($buffer, $callback);
        } catch (Throwable $e) {
            throw new StreamException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * @template T of BaseResponse
     *
     * @param  class-string<T>  $responseClass
     * @return T
     */
    protected function executeRequest(array $params, string $responseClass, bool $forAudio = false): BaseResponse
    {
        $method = $this->method($params);
        $body = $this->buildRequestBody($params, $forAudio);
        $response = $this->http->post(self::API_VERSION."/models/{$params['model']}:{$method->value}", $body);

        $this->ensureSuccessful($response);

        if ($method === GenerationMethod::PREDICT_LONG_RUNNING) {
            return new $responseClass($this->awaitOperation($response->json('name')));
        }

        $finishReason = $response->json('candidates.0.finishReason');

        if ($finishReason !== null && $finishReason !== 'STOP') {
            Log::error('Gemini API error response', ['response' => $response->json()]);

            throw new ApiException("API request failed with finishReason: {$finishReason}");
        }

        return new $responseClass($response->json() ?? []);
    }

    /**
     * Poll a long-running operation until done and return its final payload.
     * Generated video bytes are downloaded and attached as base64 under `video`.
     */
    protected function awaitOperation(string $operation): array
    {
        $interval = (int) config('gemini.long_running.poll_interval', 5);
        $deadline = time() + (int) config('gemini.long_running.timeout', 600);

        do {
            if (time() > $deadline) {
                throw new ApiException("Long-running operation timed out: {$operation}");
            }

            Sleep::for($interval)->seconds();

            $status = $this->http->get(self::API_VERSION."/{$operation}");
            $this->ensureSuccessful($status);
            $data = $status->json();
        } while (! ($data['done'] ?? false));

        if (isset($data['error'])) {
            throw new ApiException($data['error']['message'] ?? 'Long-running operation failed');
        }

        $result = $data['response'] ?? [];
        $sample = $result['generateVideoResponse']['generatedSamples'][0]
            ?? $result['generatedSamples'][0]
            ?? [];
        $uri = $sample['video']['uri'] ?? null;

        if ($uri === null) {
            return $result;
        }

        $download = $this->http->withOptions(['allow_redirects' => true])->get($uri);
        $this->ensureSuccessful($download);

        return [...$result, 'uri' => $uri, 'video' => base64_encode($download->body())];
    }

    protected function buildRequestBody(array $params, bool $forAudio = false): array
    {
        return $this->method($params)->isPredict()
            ? $this->buildPredictBody($params)
            : $this->buildGenerateContentBody($params, $forAudio);
    }

    protected function buildPredictBody(array $params): array
    {
        $parameters = array_filter([
            'temperature' => $params['temperature'] ?? null,
            'maxOutputTokens' => $params['maxTokens'] ?? null,
            'safetySettings' => $params['safetySettings'] ?? null,
        ], static fn (mixed $value): bool => $value !== null);

        return array_filter([
            'instances' => [['prompt' => $params['prompt'] ?? '', ...$this->filePart($params)]],
            'parameters' => $parameters ?: null,
        ]);
    }

    protected function buildGenerateContentBody(array $params, bool $forAudio): array
    {
        if (blank($params['prompt'] ?? null)) {
            throw new ValidationException('A prompt is required for content generation.');
        }

        $userParts = [['text' => $params['prompt']]];

        if ($filePart = $this->filePart($params)) {
            $userParts[] = $filePart;
        }

        $generationConfig = array_filter([
            'temperature' => $params['temperature'] ?? null,
            'maxOutputTokens' => $params['maxTokens'] ?? null,
        ], static fn (mixed $value): bool => $value !== null);

        if (isset($params['structuredSchema'])) {
            $generationConfig['responseMimeType'] = 'application/json';
            $generationConfig['responseSchema'] = $params['structuredSchema'];
        }

        if ($forAudio) {
            $generationConfig['responseModalities'] = ['AUDIO'];
            $generationConfig['speechConfig'] = $this->speechConfig($params);
        }

        return array_filter([
            'contents' => [...($params['history'] ?? []), ['role' => 'user', 'parts' => $userParts]],
            'systemInstruction' => isset($params['system']) ? ['parts' => [['text' => $params['system']]]] : null,
            'tools' => isset($params['functions']) ? [['functionDeclarations' => $params['functions']]] : null,
            'generationConfig' => $generationConfig ?: null,
            'safetySettings' => $params['safetySettings'] ?? config('gemini.safety_settings'),
            'cachedContent' => $params['cachedContent'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }

    /**
     * Build the inline / file-URI part for an attached file, if any.
     */
    protected function filePart(array $params): array
    {
        if (! isset($params['fileType'])) {
            return [];
        }

        if (isset($params['filePath'])) {
            $mimeType = $this->getMimeType($params['fileType'], $params['filePath']);

            return $params['fileType'] === 'image'
                ? ['inlineData' => ['mimeType' => $mimeType, 'data' => base64_encode(file_get_contents($params['filePath']))]]
                : ['fileData' => ['mimeType' => $mimeType, 'fileUri' => $this->upload($params['fileType'], $params['filePath'])]];
        }

        if (isset($params['fileUri'])) {
            return ['fileData' => ['mimeType' => $params['fileType'], 'fileUri' => $params['fileUri']]];
        }

        return [];
    }

    protected function speechConfig(array $params): array
    {
        if ($params['multiSpeaker'] ?? false) {
            return [
                'multiSpeakerVoiceConfig' => [
                    'speakerVoiceConfigs' => array_map(static fn (array $speaker): array => [
                        'speaker' => $speaker['speaker'],
                        'voiceConfig' => ['prebuiltVoiceConfig' => ['voiceName' => $speaker['voiceName']]],
                    ], $params['speakerVoices'] ?? []),
                ],
            ];
        }

        return [
            'voiceConfig' => [
                'prebuiltVoiceConfig' => [
                    'voiceName' => $params['voiceName'] ?? $this->config['default_speech_config']['voiceName'] ?? 'Kore',
                ],
            ],
        ];
    }

    protected function emitStreamLine(string $line, callable $callback): void
    {
        if (! str_starts_with($line, 'data:')) {
            return;
        }

        $data = json_decode(trim(substr($line, 5)), true);

        foreach ($data['candidates'][0]['content']['parts'] ?? [] as $part) {
            $callback($part);
        }
    }

    protected function method(array $params): GenerationMethod
    {
        return GenerationMethod::tryFrom($params['method'] ?? '') ?? GenerationMethod::GENERATE_CONTENT;
    }

    /**
     * Resolve the TTL / expireTime payload; expireTime takes precedence.
     */
    protected function expiration(array $params, bool $withDefault = true): array
    {
        return match (true) {
            filled($params['expireTime'] ?? null) => ['expireTime' => $params['expireTime']],
            filled($params['ttl'] ?? null) => ['ttl' => $params['ttl']],
            $withDefault => ['ttl' => config('gemini.caching.default_ttl')],
            default => [],
        };
    }

    /**
     * Normalise "abc" or "files/abc" into the fully-qualified resource name.
     */
    protected function resourceName(string $collection, string $name): string
    {
        if (blank($name)) {
            throw new ValidationException('Resource name is required.');
        }

        return str_starts_with($name, "{$collection}/") ? $name : "{$collection}/{$name}";
    }
}
