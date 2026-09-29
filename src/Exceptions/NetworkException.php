<?php

declare(strict_types=1);

namespace Siberfx\LaravelGemini\Exceptions;

use Throwable;

class NetworkException extends BaseException
{
    public function __construct(string $message = 'Network issue', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
