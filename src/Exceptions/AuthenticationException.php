<?php

declare(strict_types=1);

namespace Siberfx\LaravelGemini\Exceptions;

use Throwable;

class AuthenticationException extends BaseException
{
    public function __construct(string $message = 'Invalid API key or authentication issue', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
