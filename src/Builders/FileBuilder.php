<?php

declare(strict_types=1);

namespace Siberfx\LaravelGemini\Builders;

use Siberfx\LaravelGemini\Contracts\ProviderInterface;
use Siberfx\LaravelGemini\Exceptions\ValidationException;
use Siberfx\LaravelGemini\Responses\FileResponse;

class FileBuilder
{
    public function __construct(
        protected readonly ProviderInterface $provider,
    ) {}

    /**
     * Upload a local file and return its URI.
     */
    public function upload(string $fileType, string $filePath): string
    {
        if (blank($fileType) || blank($filePath)) {
            throw new ValidationException('File type and path are required for upload.');
        }

        return $this->provider->uploadFile(compact('fileType', 'filePath'));
    }

    public function get(string $fileName): FileResponse
    {
        return $this->provider->getFile($this->requireName($fileName));
    }

    public function delete(string $fileName): bool
    {
        return $this->provider->deleteFile($this->requireName($fileName));
    }

    /**
     * @param  array{pageSize?: int, pageToken?: string}  $params
     */
    public function list(array $params = []): FileResponse
    {
        return $this->provider->listFiles($params);
    }

    protected function requireName(string $fileName): string
    {
        return filled($fileName) ? $fileName : throw new ValidationException('File name is required.');
    }
}
