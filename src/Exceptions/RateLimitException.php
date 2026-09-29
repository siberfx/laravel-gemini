<?php

declare(strict_types=1);

namespace Siberfx\LaravelGemini\Exceptions;

use Throwable;

class RateLimitException extends BaseException
{
    public function __construct(
        string $message = 'Rate limit exceeded',
        public readonly ?int $retryAfter = null,
        int $code = 429,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
