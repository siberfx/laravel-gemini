<?php

declare(strict_types=1);

namespace Siberfx\LaravelGemini\Responses;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use Siberfx\LaravelGemini\Responses\Concerns\InteractsWithData;

/**
 * @implements Arrayable<string, mixed>
 */
class FileResponse implements Arrayable, JsonSerializable
{
    use InteractsWithData;

    public function files(): array
    {
        return $this->data['files'] ?? [];
    }

    public function nextPageToken(): ?string
    {
        return $this->data['nextPageToken'] ?? null;
    }

    public function name(): ?string
    {
        return $this->data['name'] ?? null;
    }

    public function uri(): ?string
    {
        return $this->data['uri'] ?? null;
    }

    public function state(): ?string
    {
        return $this->data['state'] ?? null;
    }

    public function mimeType(): ?string
    {
        return $this->data['mimeType'] ?? null;
    }

    public function displayName(): ?string
    {
        return $this->data['displayName'] ?? null;
    }
}
