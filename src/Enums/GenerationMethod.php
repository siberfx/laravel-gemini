<?php

declare(strict_types=1);

namespace Siberfx\LaravelGemini\Enums;

enum GenerationMethod: string
{
    case GENERATE_CONTENT = 'generateContent';
    case PREDICT = 'predict';
    case PREDICT_LONG_RUNNING = 'predictLongRunning';

    public function isPredict(): bool
    {
        return $this !== self::GENERATE_CONTENT;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
