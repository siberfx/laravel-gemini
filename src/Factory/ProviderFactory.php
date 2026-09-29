<?php

declare(strict_types=1);

namespace Siberfx\LaravelGemini\Factory;

use Siberfx\LaravelGemini\Contracts\ProviderInterface;
use Siberfx\LaravelGemini\Exceptions\ValidationException;

class ProviderFactory
{
    public function create(?string $alias = null, ?string $apiKey = null): ProviderInterface
    {
        $alias ??= config('gemini.default_provider');
        $config = config("gemini.providers.{$alias}");

        $class = $config['class'] ?? null;

        if (! is_string($class) || ! is_subclass_of($class, ProviderInterface::class)) {
            throw new ValidationException("Unknown or invalid provider: {$alias}");
        }

        return new $class($config, $apiKey);
    }
}
