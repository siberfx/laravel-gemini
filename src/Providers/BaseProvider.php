<?php

declare(strict_types=1);

namespace Siberfx\LaravelGemini\Providers;

use Illuminate\Http\Client\Response;
use Siberfx\LaravelGemini\Enums\FileType;
use Siberfx\LaravelGemini\Exceptions\ApiException;
use Siberfx\LaravelGemini\Exceptions\AuthenticationException;
use Siberfx\LaravelGemini\Exceptions\NetworkException;
use Siberfx\LaravelGemini\Exceptions\RateLimitException;
use Siberfx\LaravelGemini\Exceptions\ValidationException;
use Siberfx\LaravelGemini\Http\HttpClient;

abstract class BaseProvider
{
    protected HttpClient $http;

    public function __construct(
        protected readonly array $config = [],
        ?string $apiKey = null,
    ) {
        $this->http = new HttpClient(apiKey: $apiKey);
    }

    /**
     * Throw a typed exception for failed responses, otherwise hydrate the given response class.
     *
     * @template T
     *
     * @param  class-string<T>  $responseClass
     * @return T
     */
    protected function handleResponse(Response $response, string $responseClass): mixed
    {
        $this->ensureSuccessful($response);

        return new $responseClass($response->json() ?? []);
    }

    protected function ensureSuccessful(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        $status = $response->status();
        $message = $response->json('error.message') ?? "Gemini API request failed with status {$status}";
        $retryAfter = $response->header('Retry-After');

        throw match (true) {
            $status === 400 => new ValidationException($message, $status),
            $status === 401, $status === 403 => new AuthenticationException($message, $status),
            $status === 429 => new RateLimitException($message, is_numeric($retryAfter) ? (int) $retryAfter : null),
            $status >= 500 => new ApiException($message, $status),
            default => new NetworkException($message, $status),
        };
    }

    /**
     * Upload a file to the Gemini Files API and return its URI.
     *
     * @throws ValidationException
     * @throws ApiException
     */
    protected function upload(string $fileType, string $filePath): string
    {
        if (! is_file($filePath) || ! is_readable($filePath)) {
            throw new ValidationException("File does not exist or is not readable: {$filePath}");
        }

        $mimeType = FileType::fromString($fileType)->mimeTypeFor($filePath);
        $fileSize = filesize($filePath);

        // Step 1: start a resumable upload session.
        $session = $this->http
            ->withHeaders([
                'X-Goog-Upload-Protocol' => 'resumable',
                'X-Goog-Upload-Command' => 'start',
                'X-Goog-Upload-Header-Content-Length' => (string) $fileSize,
                'X-Goog-Upload-Header-Content-Type' => $mimeType,
            ])
            ->post('/upload/v1beta/files', [
                'file' => ['display_name' => basename($filePath)],
            ]);

        $this->ensureSuccessful($session);

        $uploadUrl = $session->header('X-Goog-Upload-URL') ?: $session->header('Location')
            ?: throw new ApiException('Upload URL not received from API');

        // Step 2: upload the whole file in a single chunk and finalize.
        $upload = $this->http
            ->withHeaders([
                'X-Goog-Upload-Offset' => '0',
                'X-Goog-Upload-Command' => 'upload, finalize',
            ])
            ->withBody(file_get_contents($filePath), $mimeType)
            ->post($uploadUrl);

        $this->ensureSuccessful($upload);

        return $upload->json('file.uri') ?? throw new ApiException('File URI not found in API response');
    }

    protected function getMimeType(string $fileType, string $filePath): string
    {
        return FileType::fromString($fileType)->mimeTypeFor($filePath);
    }
}
