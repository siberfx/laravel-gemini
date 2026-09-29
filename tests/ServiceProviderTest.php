<?php

declare(strict_types=1);

namespace Siberfx\LaravelGemini\Tests;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Siberfx\LaravelGemini\Exceptions\ValidationException;
use Siberfx\LaravelGemini\Facades\Gemini as GeminiFacade;
use Siberfx\LaravelGemini\Gemini;
use Siberfx\LaravelGemini\Providers\GeminiProvider;

class ServiceProviderTest extends TestCase
{
    #[Test]
    public function it_registers_a_singleton_under_class_and_alias(): void
    {
        $this->assertInstanceOf(Gemini::class, $this->app->make('gemini'));
        $this->assertSame($this->app->make(Gemini::class), $this->app->make('gemini'));
        $this->assertSame($this->app->make(Gemini::class), GeminiFacade::getFacadeRoot());
    }

    #[Test]
    public function it_merges_the_default_config(): void
    {
        $this->assertSame(GeminiProvider::class, config('gemini.providers.gemini.class'));
        $this->assertSame(3, config('gemini.retry_policy.max_retries'));
    }

    #[Test]
    public function it_rejects_unknown_providers(): void
    {
        $this->expectException(ValidationException::class);

        GeminiFacade::getProvider('missing');
    }

    #[Test]
    public function it_lists_models_via_artisan(): void
    {
        Http::fake(['*' => Http::response(['models' => [[
            'name' => 'models/gemini-2.5-flash',
            'displayName' => 'Gemini 2.5 Flash',
            'version' => '001',
            'supportedGenerationMethods' => ['generateContent', 'countTokens'],
        ]]])]);

        $this->artisan('gemini:models')
            ->expectsTable(
                ['Model', 'Name', 'Version', 'Capabilities'],
                [['models/gemini-2.5-flash', 'Gemini 2.5 Flash', '001', 'generateContent, countTokens']],
            )
            ->assertSuccessful();
    }
}
