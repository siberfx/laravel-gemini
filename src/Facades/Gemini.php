<?php

namespace Siberfx\LaravelGemini\Facades;

use Illuminate\Support\Facades\Facade;
use Siberfx\LaravelGemini\Builders\TextBuilder;
use Siberfx\LaravelGemini\Builders\ImageBuilder;
use Siberfx\LaravelGemini\Builders\VideoBuilder;
use Siberfx\LaravelGemini\Builders\AudioBuilder;
use Siberfx\LaravelGemini\Builders\FileBuilder;
use Siberfx\LaravelGemini\Builders\CacheBuilder;

class Gemini extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'gemini';
    }

    public static function text(): TextBuilder
    {
        return static::getFacadeRoot()->text();
    }

    public static function image(): ImageBuilder
    {
        return static::getFacadeRoot()->image();
    }

    public static function video(): VideoBuilder
    {
        return static::getFacadeRoot()->video();
    }

    public static function audio(): AudioBuilder
    {
        return static::getFacadeRoot()->audio();
    }

    public static function files(): FileBuilder
    {
        return static::getFacadeRoot()->files();
    }

    public static function caches(): CacheBuilder
    {
        return static::getFacadeRoot()->caches();
    }

    public static function setApiKey(string $apiKey): self
    {
        static::getFacadeRoot()->setApiKey($apiKey);
        return new static;
    }
}