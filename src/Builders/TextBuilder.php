<?php

declare(strict_types=1);

namespace Siberfx\LaravelGemini\Builders;

use Siberfx\LaravelGemini\Enums\Capability;
use Siberfx\LaravelGemini\Responses\TextResponse;

class TextBuilder extends BaseBuilder
{
    protected function capability(): Capability
    {
        return Capability::TEXT;
    }

    public function generate(): TextResponse
    {
        return $this->provider->generateText($this->params);
    }
}
