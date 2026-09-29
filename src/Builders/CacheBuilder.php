<?php

declare(strict_types=1);

namespace Siberfx\LaravelGemini\Builders;

use Siberfx\LaravelGemini\Contracts\ProviderInterface;
use Siberfx\LaravelGemini\Exceptions\ValidationException;
use Siberfx\LaravelGemini\Responses\CacheResponse;

class CacheBuilder
{
    public function __construct(
        protected readonly ProviderInterface $provider,
    ) {}

    public function create(
        string $model,
        array $contents,
        ?string $systemInstruction = null,
        array $tools = [],
        array $toolConfig = [],
        ?string $displayName = null,
        ?string $ttl = null,
        ?string $expireTime = null,
    ): CacheResponse {
        if (blank($model) || $contents === []) {
            throw new ValidationException('Model and contents are required for creating cache.');
        }

        return $this->provider->createCachedContent(
            compact('model', 'contents', 'systemInstruction', 'tools', 'toolConfig', 'displayName', 'ttl', 'expireTime')
        );
    }

    public function list(?int $pageSize = null, ?string $pageToken = null): CacheResponse
    {
        return $this->provider->listCachedContents(array_filter(compact('pageSize', 'pageToken')));
    }

    public function get(string $name): CacheResponse
    {
        return $this->provider->getCachedContent($this->requireName($name));
    }

    public function update(string $name, ?string $ttl = null, ?string $expireTime = null): CacheResponse
    {
        if (blank($ttl) && blank($expireTime)) {
            throw new ValidationException('TTL or expireTime is required for update.');
        }

        return $this->provider->updateCachedContent(
            $this->requireName($name),
            array_filter(compact('ttl', 'expireTime')),
        );
    }

    public function delete(string $name): bool
    {
        return $this->provider->deleteCachedContent($this->requireName($name));
    }

    protected function requireName(string $name): string
    {
        return filled($name) ? $name : throw new ValidationException('Cache name is required.');
    }
}
