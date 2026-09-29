<?php

declare(strict_types=1);

namespace Siberfx\LaravelGemini\Responses;

use Siberfx\LaravelGemini\Exceptions\ApiException;

class AudioResponse extends BaseResponse
{
    /**
     * Raw audio bytes (PCM for `audio/L16` responses).
     */
    public function content(): string
    {
        $finishReason = $this->finishReason();

        if ($finishReason !== null && $finishReason !== 'STOP') {
            throw new ApiException("Failed to retrieve audio content. Finish reason: {$finishReason}");
        }

        $data = $this->inlineData()['data']
            ?? throw new ApiException('Failed to retrieve audio content. No inlineData found.');

        return base64_decode($data);
    }

    /**
     * Audio metadata parsed from the MIME type, e.g. "audio/L16;codec=pcm;rate=24000".
     *
     * @return array{mimeType: ?string, sampleRate: int, channels: int, bitsPerSample: int}
     */
    public function getAudioMeta(): array
    {
        $mime = $this->inlineData()['mimeType'] ?? null;
        $meta = ['mimeType' => $mime, 'sampleRate' => 16000, 'channels' => 1, 'bitsPerSample' => 16];

        if ($this->isPcm($mime)) {
            foreach (array_map(trim(...), explode(';', $mime)) as $part) {
                match (true) {
                    str_starts_with($part, 'rate=') => $meta['sampleRate'] = (int) substr($part, 5),
                    str_starts_with($part, 'channels=') => $meta['channels'] = (int) substr($part, 9),
                    default => null,
                };
            }
        }

        return $meta;
    }

    /**
     * Save the audio; raw PCM is wrapped in a WAV container.
     */
    public function save(string $path): void
    {
        $raw = $this->content();
        $meta = $this->getAudioMeta();

        file_put_contents($path, $this->isPcm($meta['mimeType'])
            ? $this->pcmToWav($raw, $meta['sampleRate'], $meta['channels'], $meta['bitsPerSample'])
            : $raw);
    }

    private function isPcm(?string $mime): bool
    {
        return $mime !== null && str_starts_with($mime, 'audio/L16');
    }

    private function pcmToWav(string $pcmData, int $sampleRate, int $channels, int $bitsPerSample): string
    {
        $byteRate = intdiv($sampleRate * $channels * $bitsPerSample, 8);
        $blockAlign = intdiv($channels * $bitsPerSample, 8);
        $dataSize = strlen($pcmData);

        return pack('N', 0x52494646)    // "RIFF"
            .pack('V', 36 + $dataSize)  // ChunkSize (little-endian)
            .pack('N2', 0x57415645, 0x666D7420) // "WAVE" "fmt "
            .pack('V', 16)              // Subchunk1Size
            .pack('v', 1)               // PCM format
            .pack('v', $channels)
            .pack('V', $sampleRate)
            .pack('V', $byteRate)
            .pack('v', $blockAlign)
            .pack('v', $bitsPerSample)
            .pack('N', 0x64617461)      // "data"
            .pack('V', $dataSize)
            .$pcmData;
    }
}
