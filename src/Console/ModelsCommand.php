<?php

declare(strict_types=1);

namespace Siberfx\LaravelGemini\Console;

use Illuminate\Console\Command;
use Siberfx\LaravelGemini\Gemini;

class ModelsCommand extends Command
{
    protected $signature = 'gemini:models';

    protected $description = 'List available Gemini models';

    public function handle(Gemini $gemini): int
    {
        $rows = array_map(static fn (array $model): array => [
            $model['name'],
            $model['displayName'] ?? 'N/A',
            $model['version'] ?? 'N/A',
            implode(', ', $model['supportedGenerationMethods'] ?? []),
        ], $gemini->models());

        $this->table(['Model', 'Name', 'Version', 'Capabilities'], $rows);

        return self::SUCCESS;
    }
}
