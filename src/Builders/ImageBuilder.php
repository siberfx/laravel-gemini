<?php

declare(strict_types=1);

namespace Siberfx\LaravelGemini\Builders;

use Siberfx\LaravelGemini\Enums\Capability;
use Siberfx\LaravelGemini\Responses\ImageResponse;

class ImageBuilder extends BaseBuilder
{
    protected function capability(): Capability
    {
        return Capability::IMAGE;
    }

    public function generate(): ImageResponse
    {
        return $this->provider->generateImage($this->params);
    }
}
