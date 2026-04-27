<?php

namespace Siberfx\LaravelGemini\Console;

use Illuminate\Console\Command;
use Siberfx\LaravelGemini\Facades\Gemini;

class ModelsCommand extends Command
{
    protected $signature = 'gemini:models';

    protected $description = 'List available Gemini models';

    public function handle(): void
    {
        $models = Gemini::models();
        $tableData = array_map(function ($model) {
            return [
                $model['name'],
                $model['displayName'],
                $model['version'] ?? 'N/A',
                implode(', ', $model['supportedGenerationMethods'] ?? []),
            ];
        }, $models);
        $this->table(['Model', 'Name', 'Version', 'Capabilities'], $tableData);
    }
}