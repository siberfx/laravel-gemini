<?php

declare(strict_types=1);

namespace Siberfx\LaravelGemini\Responses;

use Siberfx\LaravelGemini\Exceptions\ApiException;

class ImageResponse extends BaseResponse
{
    /**
     * Raw image bytes.
     */
    public function content(): string
    {
        $inlineData = $this->inlineData()
            ?? $this->data['predictions'][0] ?? null;

        $encoded = $inlineData['data'] ?? $inlineData['bytesBase64Encoded']
            ?? throw new ApiException('Failed to retrieve image content. No inlineData found.');

        return base64_decode($encoded);
    }

    public function mimeType(): ?string
    {
        return $this->inlineData()['mimeType'] ?? $this->data['predictions'][0]['mimeType'] ?? null;
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
