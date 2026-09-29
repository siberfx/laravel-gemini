<?php

declare(strict_types=1);

namespace Siberfx\LaravelGemini\Enums;

use Siberfx\LaravelGemini\Exceptions\ValidationException;

enum FileType: string
{
    case IMAGE = 'image';
    case VIDEO = 'video';
    case AUDIO = 'audio';
    case DOCUMENT = 'document';

    /**
     * @return array<string, string> extension => MIME type
     */
    public function mimeTypes(): array
    {
        return match ($this) {
            self::IMAGE => [
                'png' => 'image/png',
                'jpeg' => 'image/jpeg',
                'jpg' => 'image/jpeg',
                'webp' => 'image/webp',
                'heic' => 'image/heic',
                'heif' => 'image/heif',
            ],
            self::VIDEO => [
                'mp4' => 'video/mp4',
                'mpeg' => 'video/mpeg',
                'mov' => 'video/mov',
                'avi' => 'video/avi',
                'flv' => 'video/x-flv',
                'mpg' => 'video/mpg',
                'webm' => 'video/webm',
                'wmv' => 'video/wmv',
                '3gpp' => 'video/3gpp',
            ],
            self::AUDIO => [
                'wav' => 'audio/x-wav',
                'mp3' => 'audio/mp3',
                'aiff' => 'audio/aiff',
                'aac' => 'audio/aac',
                'ogg' => 'audio/ogg',
                'flac' => 'audio/flac',
            ],
            self::DOCUMENT => [
                'pdf' => 'application/pdf',
                'txt' => 'text/plain',
                'md' => 'text/markdown',
            ],
        };
    }

    public function mimeTypeFor(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return $this->mimeTypes()[$extension]
            ?? throw new ValidationException("Unsupported {$this->value} format: {$extension}");
    }

    public static function fromString(string $type): self
    {
        return self::tryFrom($type) ?? throw new ValidationException(
            "Invalid file type: {$type}. Allowed types: ".implode(', ', array_column(self::cases(), 'value'))
        );
    }
}
