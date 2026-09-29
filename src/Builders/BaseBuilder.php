<?php

declare(strict_types=1);

namespace Siberfx\LaravelGemini\Builders;

use Illuminate\Contracts\Database\Query\Builder;
use Siberfx\LaravelGemini\Contracts\ProviderInterface;
use Siberfx\LaravelGemini\Enums\Capability;
use Siberfx\LaravelGemini\Enums\GenerationMethod;
use Siberfx\LaravelGemini\Exceptions\ValidationException;
use Siberfx\LaravelGemini\Responses\BaseResponse;
use Siberfx\LaravelGemini\Responses\CacheResponse;

abstract class BaseBuilder
{
    protected array $params = [];

    public function __construct(
        protected readonly ProviderInterface $provider,
    ) {
        $capability = $this->capability()->value;
        $provider = config('gemini.default_provider');

        $this->params = [
            'capability' => $capability,
            'defaultProvider' => $provider,
            'model' => config("gemini.providers.{$provider}.models.{$capability}")
                ?: throw new ValidationException("Default model for {$capability} not found in configuration."),
            'method' => config("gemini.providers.{$provider}.methods.{$capability}")
                ?: throw new ValidationException("Default method for {$capability} not found in configuration."),
        ];
    }

    abstract protected function capability(): Capability;

    abstract public function generate(): BaseResponse;

    public function model(?string $model = null): static
    {
        $this->params['model'] = $model ?? $this->params['model'];

        return $this;
    }

    public function method(string|GenerationMethod $method): static
    {
        $method = $method instanceof GenerationMethod
            ? $method
            : GenerationMethod::tryFrom($method) ?? throw new ValidationException(
                "Invalid method: {$method}. Supported: ".implode(', ', GenerationMethod::values()).'.'
            );

        $this->params['method'] = $method->value;

        return $this;
    }

    public function prompt(string $prompt): static
    {
        return $this->set('prompt', $prompt);
    }

    public function system(string $system): static
    {
        return $this->set('system', $system);
    }

    public function history(array $history): static
    {
        return $this->set('history', $history);
    }

    /**
     * Build the history from an Eloquent query / relation.
     *
     * @param  Builder  $query
     */
    public function historyFromModel(mixed $query, string $bodyColumn, string $roleColumn): static
    {
        return $this->history(
            $query->get()
                ->map(static fn (object $item): array => [
                    'role' => $item->{$roleColumn},
                    'parts' => [['text' => $item->{$bodyColumn}]],
                ])
                ->all()
        );
    }

    public function temperature(float $value): static
    {
        return $this->set('temperature', $value);
    }

    public function maxTokens(int $value): static
    {
        return $this->set('maxTokens', $value);
    }

    public function safetySettings(array $settings): static
    {
        return $this->set('safetySettings', $settings);
    }

    public function functionCalls(array $functions): static
    {
        return $this->set('functions', $functions);
    }

    public function structuredSchema(array $schema): static
    {
        return $this->set('structuredSchema', $schema);
    }

    /**
     * Attach a local file; images are inlined, other types are uploaded to the Files API.
     */
    public function upload(string $fileType, string $filePath): static
    {
        $this->params['fileType'] = $fileType;
        $this->params['filePath'] = $filePath;

        return $this;
    }

    /**
     * Attach an already-uploaded file by URI.
     */
    public function file(string $mimeType, string $fileUri): static
    {
        $this->params['fileType'] = $mimeType;
        $this->params['fileUri'] = $fileUri;

        return $this;
    }

    /**
     * Create a cached content from the current prompt / history / system and return its name.
     */
    public function cache(
        array $tools = [],
        array $toolConfig = [],
        ?string $displayName = null,
        ?string $ttl = null,
        ?string $expireTime = null,
    ): string {
        $contents = $this->params['history'] ?? [];

        if (isset($this->params['prompt'])) {
            $contents[] = ['role' => 'user', 'parts' => [['text' => $this->params['prompt']]]];
        }

        return $this->provider->createCachedContent([
            'model' => $this->params['model'],
            'contents' => $contents,
            'systemInstruction' => $this->params['system'] ?? null,
            'tools' => $tools,
            'toolConfig' => $toolConfig,
            'displayName' => $displayName,
            'ttl' => $ttl ?? config('gemini.caching.default_ttl'),
            'expireTime' => $expireTime,
        ])->name();
    }

    public function getCache(string $name): CacheResponse
    {
        if (blank($name)) {
            throw new ValidationException('Cache name is required.');
        }

        return $this->provider->getCachedContent($name);
    }

    public function cachedContent(string $name): static
    {
        return $this->set('cachedContent', $name);
    }

    /**
     * @param  callable(array $part): void  $callback
     */
    public function stream(callable $callback): void
    {
        $this->provider->streaming($this->params, $callback);
    }

    public function toArray(): array
    {
        return $this->params;
    }

    protected function set(string $key, mixed $value): static
    {
        $this->params[$key] = $value;

        return $this;
    }
}
