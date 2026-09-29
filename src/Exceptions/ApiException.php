<?php

declare(strict_types=1);

namespace Siberfx\LaravelGemini\Exceptions;

use Throwable;

class ApiException extends BaseException
{
    public function __construct(string $message = 'API error occurred', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
