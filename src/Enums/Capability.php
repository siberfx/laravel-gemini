<?php

declare(strict_types=1);

namespace Siberfx\LaravelGemini\Enums;

enum Capability: string
{
    case TEXT = 'text';
    case IMAGE = 'image';
    case VIDEO = 'video';
    case AUDIO = 'audio';
}
