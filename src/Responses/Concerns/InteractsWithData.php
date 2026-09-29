<?php

declare(strict_types=1);

namespace Siberfx\LaravelGemini\Responses\Concerns;

use Illuminate\Support\Arr;

trait InteractsWithData
{
    public function __construct(
        protected readonly array $data = [],
    ) {}

    /**
     * Read a value from the raw payload using "dot" notation.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->data, $key, $default);
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function json(int $flags = 0): string
    {
        return json_encode($this->data, $flags | JSON_THROW_ON_ERROR);
    }

    public function jsonSerialize(): array
    {
        return $this->data;
    }
}
