<?php

namespace Siberfx\LaravelGemini\Builders;

use Siberfx\LaravelGemini\Enums\Capability;
use Siberfx\LaravelGemini\Responses\TextResponse;

class TextBuilder extends BaseBuilder
{
    protected function getCapability(): string
    {
        return Capability::TEXT->value;
    }

    public function generate(): TextResponse
    {
        return $this->provider->generateText($this->params);
    }
}