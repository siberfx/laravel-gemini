<?php

declare(strict_types=1);

namespace Siberfx\LaravelGemini\Builders;

use Siberfx\LaravelGemini\Enums\Capability;
use Siberfx\LaravelGemini\Responses\VideoResponse;

class VideoBuilder extends BaseBuilder
{
    protected function capability(): Capability
    {
        return Capability::VIDEO;
    }

    public function generate(): VideoResponse
    {
        return $this->provider->generateVideo($this->params);
    }
}
