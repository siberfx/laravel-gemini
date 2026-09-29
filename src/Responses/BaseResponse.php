<?php

declare(strict_types=1);

namespace Siberfx\LaravelGemini\Responses;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use Siberfx\LaravelGemini\Responses\Concerns\InteractsWithData;

/**
 * @implements Arrayable<string, mixed>
 */
abstract class BaseResponse implements Arrayable, JsonSerializable
{
    use InteractsWithData;

    abstract public function content(): string;

    public function model(): string
    {
        return $this->data['modelVersion'] ?? '';
    }

    public function usage(): array
    {
        return $this->data['usageMetadata'] ?? [];
    }

    public function requestId(): string
    {
        return $this->data['responseId'] ?? '';
    }

    /**
     * Parts of the first candidate.
     *
     * @return list<array<string, mixed>>
     */
    public function parts(): array
    {
        return $this->data['candidates'][0]['content']['parts'] ?? [];
    }

    public function finishReason(): ?string
    {
        return $this->data['candidates'][0]['finishReason'] ?? null;
    }

    /**
     * First part carrying inline (base64) data, if any.
     */
    protected function inlineData(): ?array
    {
        foreach ($this->parts() as $part) {
            if (isset($part['inlineData'])) {
                return $part['inlineData'];
            }
        }

        return null;
    }
}
