<?php

declare(strict_types=1);

namespace Siberfx\LaravelGemini\Http;

use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Siberfx\LaravelGemini\Exceptions\NetworkException;
use Throwable;

/**
 * Thin immutable wrapper around Laravel's HTTP client: every `with*` call
 * returns a new instance so per-request headers never leak into later calls.
 */
class HttpClient
{
    protected PendingRequest $client;

    public function __construct(?string $baseUrl = null, ?string $apiKey = null)
    {
        $this->client = Http::baseUrl($baseUrl ?? config('gemini.base_uri'))
            ->withHeaders(['x-goog-api-key' => $apiKey ?? config('gemini.api_key')])
            ->timeout((int) config('gemini.timeout', 30))
            ->retry(
                times: (int) config('gemini.retry_policy.max_retries', 3),
                sleepMilliseconds: $this->retryDelay(...),
                when: $this->shouldRetry(...),
                throw: false,
            );
    }

    public function withHeaders(array $headers): static
    {
        return $this->tap(static fn (PendingRequest $client) => $client->withHeaders($headers));
    }

    public function withBody(string $content, string $contentType = 'application/json'): static
    {
        return $this->tap(static fn (PendingRequest $client) => $client->withBody($content, $contentType));
    }

    public function withOptions(array $options): static
    {
        return $this->tap(static fn (PendingRequest $client) => $client->withOptions($options));
    }

    public function post(string $url, array $data = []): Response
    {
        return $this->send(fn () => $this->client->post($url, $data));
    }

    public function get(string $url, array|string|null $query = null): Response
    {
        return $this->send(fn () => $this->client->get($url, $query));
    }

    public function patch(string $url, array $data = []): Response
    {
        return $this->send(fn () => $this->client->patch($url, $data));
    }

    public function delete(string $url): Response
    {
        return $this->send(fn () => $this->client->delete($url));
    }

    /**
     * @param  Closure(): Response  $request
     */
    protected function send(Closure $request): Response
    {
        try {
            return $request();
        } catch (ConnectionException $e) {
            throw new NetworkException($e->getMessage(), previous: $e);
        }
    }

    protected function tap(callable $callback): static
    {
        $clone = clone $this;
        $clone->client = clone $this->client;
        $callback($clone->client);

        return $clone;
    }

    protected function shouldRetry(Throwable $exception): bool
    {
        return match (true) {
            $exception instanceof ConnectionException => true,
            $exception instanceof RequestException => $exception->response->status() === 429
                || $exception->response->serverError(),
            default => false,
        };
    }

    protected function retryDelay(int $attempt, Throwable $exception): int
    {
        $retryAfter = $exception instanceof RequestException
            ? $exception->response->header('Retry-After')
            : '';

        return is_numeric($retryAfter)
            ? (int) $retryAfter * 1000
            : (int) config('gemini.retry_policy.retry_delay', 1000);
    }
}
