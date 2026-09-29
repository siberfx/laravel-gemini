<?php

declare(strict_types=1);

namespace Siberfx\LaravelGemini\Builders;

use Siberfx\LaravelGemini\Enums\Capability;
use Siberfx\LaravelGemini\Exceptions\ValidationException;
use Siberfx\LaravelGemini\Responses\AudioResponse;

class AudioBuilder extends BaseBuilder
{
    protected function capability(): Capability
    {
        return Capability::AUDIO;
    }

    /**
     * Set the voice for single-speaker TTS, e.g. 'Kore' or 'Puck'.
     */
    public function voiceName(string $voiceName): static
    {
        $this->params['voiceName'] = $voiceName;
        $this->params['multiSpeaker'] = false;

        return $this;
    }

    /**
     * Set speaker voices for multi-speaker TTS.
     *
     * @param  list<array{speaker: string, voiceName: string}>  $speakerVoices
     */
    public function speakerVoices(array $speakerVoices): static
    {
        $this->params['speakerVoices'] = $speakerVoices;
        $this->params['multiSpeaker'] = true;

        return $this;
    }

    public function generate(): AudioResponse
    {
        $multiSpeaker = $this->params['multiSpeaker'] ?? false;

        match (true) {
            $multiSpeaker && blank($this->params['speakerVoices'] ?? null) => throw new ValidationException('Speaker voices are required for multi-speaker TTS.'),
            ! $multiSpeaker && blank($this->params['voiceName'] ?? null) => throw new ValidationException('Voice name is required for single-speaker TTS.'),
            default => null,
        };

        return $this->provider->generateAudio($this->params);
    }
}
