<?php

declare(strict_types=1);

namespace Siberfx\LaravelGemini\Exceptions;

use Throwable;

class ValidationException extends BaseException
{
    public function __construct(string $message = 'Invalid input parameters', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
