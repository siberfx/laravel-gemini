<?php

declare(strict_types=1);

namespace Siberfx\LaravelGemini;

use Illuminate\Support\ServiceProvider;
use Siberfx\LaravelGemini\Console\ModelsCommand;
use Siberfx\LaravelGemini\Factory\ProviderFactory;

class GeminiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/Config/gemini.php', 'gemini');

        $this->app->singleton(ProviderFactory::class);
        $this->app->singleton(Gemini::class);
        $this->app->alias(Gemini::class, 'gemini');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/Config/gemini.php' => config_path('gemini.php'),
        ], 'gemini-config');

        if ($this->app->runningInConsole()) {
            $this->commands([ModelsCommand::class]);
        }
    }
}
