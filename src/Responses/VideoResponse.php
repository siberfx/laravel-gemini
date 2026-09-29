<?php

declare(strict_types=1);

namespace Siberfx\LaravelGemini\Responses;

class VideoResponse extends BaseResponse
{
    /**
     * Raw video bytes (downloaded once the long-running operation completes).
     */
    public function content(): string
    {
        return base64_decode($this->data['video'] ?? '');
    }

    public function url(): string
    {
        return $this->data['uri'] ?? '';
    }

    public function save(string $path): void
    {
        file_put_contents($path, $this->content());
    }
}
