<?php

namespace Siberfx\LaravelGemini\Builders;

use Siberfx\LaravelGemini\Enums\Capability;
use Siberfx\LaravelGemini\Responses\ImageResponse;

class ImageBuilder extends BaseBuilder
{
    protected function getCapability(): string
    {
        return Capability::IMAGE->value;
    }

    public function generate(): ImageResponse
    {
        return $this->provider->generateImage($this->params);
    }
}