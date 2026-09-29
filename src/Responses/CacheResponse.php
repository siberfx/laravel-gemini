<?php

declare(strict_types=1);

namespace Siberfx\LaravelGemini\Responses;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use Siberfx\LaravelGemini\Responses\Concerns\InteractsWithData;

/**
 * @implements Arrayable<string, mixed>
 */
class CacheResponse implements Arrayable, JsonSerializable
{
    use InteractsWithData;

    public function name(): string
    {
        return $this->data['name'] ?? '';
    }

    public function model(): string
    {
        return $this->data['model'] ?? '';
    }

    public function createTime(): string
    {
        return $this->data['createTime'] ?? '';
    }

    public function updateTime(): string
    {
        return $this->data['updateTime'] ?? '';
    }

    public function expireTime(): string
    {
        return $this->data['expireTime'] ?? '';
    }

    public function displayName(): string
    {
        return $this->data['displayName'] ?? '';
    }

    public function usageMetadata(): array
    {
        return $this->data['usageMetadata'] ?? [];
    }

    /**
     * Cached contents returned by a list call.
     */
    public function cachedContents(): array
    {
        return $this->data['cachedContents'] ?? [];
    }

    public function nextPageToken(): ?string
    {
        return $this->data['nextPageToken'] ?? null;
    }
}
