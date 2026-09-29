<?php

declare(strict_types=1);

namespace Siberfx\LaravelGemini;

use Siberfx\LaravelGemini\Builders\AudioBuilder;
use Siberfx\LaravelGemini\Builders\CacheBuilder;
use Siberfx\LaravelGemini\Builders\FileBuilder;
use Siberfx\LaravelGemini\Builders\ImageBuilder;
use Siberfx\LaravelGemini\Builders\TextBuilder;
use Siberfx\LaravelGemini\Builders\VideoBuilder;
use Siberfx\LaravelGemini\Contracts\ProviderInterface;
use Siberfx\LaravelGemini\Factory\ProviderFactory;

class Gemini
{
    protected ?string $apiKey = null;

    public function __construct(
        protected readonly ProviderFactory $factory,
    ) {}

    public function setApiKey(string $apiKey): static
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    public function text(): TextBuilder
    {
        return new TextBuilder($this->getProvider());
    }

    public function image(): ImageBuilder
    {
        return new ImageBuilder($this->getProvider());
    }

    public function video(): VideoBuilder
    {
        return new VideoBuilder($this->getProvider());
    }

    public function audio(): AudioBuilder
    {
        return new AudioBuilder($this->getProvider());
    }

    public function files(): FileBuilder
    {
        return new FileBuilder($this->getProvider());
    }

    public function caches(): CacheBuilder
    {
        return new CacheBuilder($this->getProvider());
    }

    public function models(): array
    {
        return $this->getProvider()->models();
    }

    public function embeddings(array $params): array
    {
        return $this->getProvider()->embeddings($params);
    }

    public function getProvider(?string $alias = null): ProviderInterface
    {
        return $this->factory->create($alias, $this->apiKey);
    }
}
