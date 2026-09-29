<?php

declare(strict_types=1);

namespace Siberfx\LaravelGemini\Contracts;

use Siberfx\LaravelGemini\Responses\AudioResponse;
use Siberfx\LaravelGemini\Responses\CacheResponse;
use Siberfx\LaravelGemini\Responses\FileResponse;
use Siberfx\LaravelGemini\Responses\ImageResponse;
use Siberfx\LaravelGemini\Responses\TextResponse;
use Siberfx\LaravelGemini\Responses\VideoResponse;

interface ProviderInterface
{
    public function generateText(array $params): TextResponse;

    public function generateImage(array $params): ImageResponse;

    public function generateVideo(array $params): VideoResponse;

    public function generateAudio(array $params): AudioResponse;

    public function embeddings(array $params): array;

    public function uploadFile(array $params): string;

    public function listFiles(array $params = []): FileResponse;

    public function getFile(string $fileName): FileResponse;

    public function deleteFile(string $fileName): bool;

    public function createCachedContent(array $params): CacheResponse;

    public function listCachedContents(array $params = []): CacheResponse;

    public function getCachedContent(string $name): CacheResponse;

    public function updateCachedContent(string $name, array $expiration): CacheResponse;

    public function deleteCachedContent(string $name): bool;

    public function models(): array;

    public function streaming(array $params, callable $callback): void;
}
