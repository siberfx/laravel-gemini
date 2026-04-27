<?php

namespace Siberfx\LaravelGemini;

use Siberfx\LaravelGemini\Builders\TextBuilder;
use Siberfx\LaravelGemini\Builders\ImageBuilder;
use Siberfx\LaravelGemini\Builders\VideoBuilder;
use Siberfx\LaravelGemini\Builders\AudioBuilder;
use Siberfx\LaravelGemini\Builders\FileBuilder;
use Siberfx\LaravelGemini\Builders\CacheBuilder;
use Siberfx\LaravelGemini\Factory\ProviderFactory;

class Gemini
{
    protected ProviderFactory $factory;

    protected ?string $apiKey = null;

    public function __construct(ProviderFactory $factory)
    {
        $this->factory = $factory;
    }

    public function setApiKey(string $apiKey): self
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

    public function models()
    {
        return $this->getProvider()->models();
    }

    public function embeddings(array $params): array
    {
        return $this->getProvider()->embeddings($params);
    }

    public function getProvider(?string $alias = null)
    {
        return $this->factory->create($alias, $this->apiKey);
    }
}
