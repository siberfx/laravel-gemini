<?php

declare(strict_types=1);

namespace Siberfx\LaravelGemini\Facades;

use Illuminate\Support\Facades\Facade;
use Siberfx\LaravelGemini\Builders\AudioBuilder;
use Siberfx\LaravelGemini\Builders\CacheBuilder;
use Siberfx\LaravelGemini\Builders\FileBuilder;
use Siberfx\LaravelGemini\Builders\ImageBuilder;
use Siberfx\LaravelGemini\Builders\TextBuilder;
use Siberfx\LaravelGemini\Builders\VideoBuilder;
use Siberfx\LaravelGemini\Contracts\ProviderInterface;

/**
 * @method static \Siberfx\LaravelGemini\Gemini setApiKey(string $apiKey)
 * @method static TextBuilder text()
 * @method static ImageBuilder image()
 * @method static VideoBuilder video()
 * @method static AudioBuilder audio()
 * @method static FileBuilder files()
 * @method static CacheBuilder caches()
 * @method static array models()
 * @method static array embeddings(array $params)
 * @method static ProviderInterface getProvider(?string $alias = null)
 *
 * @see \Siberfx\LaravelGemini\Gemini
 */
class Gemini extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'gemini';
    }
}
