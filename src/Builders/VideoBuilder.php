<?php

namespace Siberfx\LaravelGemini\Builders;

use Siberfx\LaravelGemini\Enums\Capability;
use Siberfx\LaravelGemini\Responses\VideoResponse;

class VideoBuilder extends BaseBuilder
{
    protected function getCapability(): string
    {
        return Capability::VIDEO->value;
    }

    public function generate(): VideoResponse
    {
        return $this->provider->generateVideo($this->params);
    }
}