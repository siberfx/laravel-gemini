<?php

declare(strict_types=1);

namespace Siberfx\LaravelGemini\Exceptions;

use Throwable;

class StreamException extends BaseException
{
    public function __construct(string $message = 'Streaming interrupted', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
