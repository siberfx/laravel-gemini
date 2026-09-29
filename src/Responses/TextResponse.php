<?php

declare(strict_types=1);

namespace Siberfx\LaravelGemini\Responses;

class TextResponse extends BaseResponse
{
    /**
     * Concatenated text of all non-thought parts.
     */
    public function content(): string
    {
        return implode('', array_map(
            static fn (array $part): string => ($part['thought'] ?? false) ? '' : ($part['text'] ?? ''),
            $this->parts(),
        ));
    }

    /**
     * Decode the text content as JSON (for structured output).
     */
    public function decode(bool $associative = true): mixed
    {
        return json_decode($this->content(), $associative, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * Function calls requested by the model.
     *
     * @return list<array{name: string, args?: array}>
     */
    public function functionCalls(): array
    {
        return array_values(array_filter(array_column($this->parts(), 'functionCall')));
    }
}
