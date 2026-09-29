<?php

declare(strict_types=1);

namespace Siberfx\LaravelGemini\Tests;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Orchestra\Testbench\TestCase as Orchestra;
use Siberfx\LaravelGemini\Facades\Gemini;
use Siberfx\LaravelGemini\GeminiServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        Sleep::fake();
    }

    protected function getPackageProviders($app): array
    {
        return [GeminiServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return ['Gemini' => Gemini::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('gemini.api_key', 'test-key');
        $app['config']->set('gemini.retry_policy.retry_delay', 1);
    }

    /**
     * Minimal generateContent payload with the given parts.
     */
    protected function candidate(array $parts, string $finishReason = 'STOP'): array
    {
        return ['candidates' => [['content' => ['parts' => $parts], 'finishReason' => $finishReason]]];
    }
}
