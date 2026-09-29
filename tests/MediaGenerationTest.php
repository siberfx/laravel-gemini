<?php

declare(strict_types=1);

namespace Siberfx\LaravelGemini\Tests;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Siberfx\LaravelGemini\Exceptions\ApiException;
use Siberfx\LaravelGemini\Exceptions\ValidationException;
use Siberfx\LaravelGemini\Facades\Gemini;

class MediaGenerationTest extends TestCase
{
    #[Test]
    public function it_generates_an_image(): void
    {
        Http::fake(['*' => Http::response($this->candidate([
            ['text' => 'Here you go'],
            ['inlineData' => ['mimeType' => 'image/png', 'data' => base64_encode('PNGBYTES')]],
        ]))]);

        $image = Gemini::image()->prompt('A cat')->generate();

        $this->assertSame('PNGBYTES', $image->content());
        $this->assertSame('image/png', $image->mimeType());
    }

    #[Test]
    public function it_reads_imagen_predictions(): void
    {
        Http::fake(['*' => Http::response(['predictions' => [
            ['bytesBase64Encoded' => base64_encode('IMG'), 'mimeType' => 'image/png'],
        ]])]);

        $image = Gemini::image()->model('imagen-4.0-generate-001')->method('predict')->prompt('A cat')->generate();

        $this->assertSame('IMG', $image->content());
        Http::assertSent(fn (Request $request): bool => $request->data() === ['instances' => [['prompt' => 'A cat']]]);
    }

    #[Test]
    public function it_polls_and_downloads_long_running_video(): void
    {
        Http::fake([
            '*:predictLongRunning' => Http::response(['name' => 'models/veo/operations/op1']),
            '*/v1beta/models/veo/operations/op1' => Http::sequence()
                ->push(['done' => false])
                ->push(['done' => true, 'response' => ['generateVideoResponse' => [
                    'generatedSamples' => [['video' => ['uri' => 'https://download.test/v.mp4']]],
                ]]]),
            'download.test/*' => Http::response('MP4BYTES'),
        ]);

        $video = Gemini::video()->prompt('Waves')->generate();

        $this->assertSame('MP4BYTES', $video->content());
        $this->assertSame('https://download.test/v.mp4', $video->url());
    }

    #[Test]
    public function it_surfaces_long_running_operation_errors(): void
    {
        Http::fake([
            '*:predictLongRunning' => Http::response(['name' => 'models/veo/operations/op1']),
            '*/operations/op1' => Http::response(['done' => true, 'error' => ['message' => 'quota']]),
        ]);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('quota');

        Gemini::video()->prompt('Waves')->generate();
    }

    #[Test]
    public function it_times_out_long_running_operations(): void
    {
        config(['gemini.long_running.timeout' => -1]);

        Http::fake(['*:predictLongRunning' => Http::response(['name' => 'models/veo/operations/op1'])]);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('timed out');

        Gemini::video()->prompt('Waves')->generate();
    }

    #[Test]
    public function it_generates_single_speaker_audio_and_saves_wav(): void
    {
        Http::fake(['*' => Http::response($this->candidate([
            ['inlineData' => ['mimeType' => 'audio/L16;codec=pcm;rate=24000', 'data' => base64_encode("\x01\x02\x03\x04")]],
        ]))]);

        $audio = Gemini::audio()->voiceName('Puck')->prompt('Hello')->generate();

        Http::assertSent(fn (Request $request): bool => $request['generationConfig']['responseModalities'] === ['AUDIO']
            && $request['generationConfig']['speechConfig']['voiceConfig']['prebuiltVoiceConfig']['voiceName'] === 'Puck');

        $path = tempnam(sys_get_temp_dir(), 'gem');
        $audio->save($path);
        $wav = file_get_contents($path);
        @unlink($path);

        $this->assertSame('RIFF', substr($wav, 0, 4));
        $this->assertSame(40, unpack('V', substr($wav, 4, 4))[1]);
        $this->assertSame(24000, unpack('V', substr($wav, 24, 4))[1]);
        $this->assertSame(4, unpack('V', substr($wav, 40, 4))[1]);
        $this->assertSame("\x01\x02\x03\x04", substr($wav, 44));
    }

    #[Test]
    public function it_maps_multi_speaker_voices(): void
    {
        Http::fake(['*' => Http::response($this->candidate([
            ['inlineData' => ['mimeType' => 'audio/L16;rate=24000', 'data' => base64_encode('x')]],
        ]))]);

        Gemini::audio()
            ->speakerVoices([['speaker' => 'Joe', 'voiceName' => 'Kore'], ['speaker' => 'Jane', 'voiceName' => 'Puck']])
            ->prompt('Joe: hi. Jane: hey.')
            ->generate();

        Http::assertSent(fn (Request $request): bool => $request['generationConfig']['speechConfig']
            === ['multiSpeakerVoiceConfig' => ['speakerVoiceConfigs' => [
                ['speaker' => 'Joe', 'voiceConfig' => ['prebuiltVoiceConfig' => ['voiceName' => 'Kore']]],
                ['speaker' => 'Jane', 'voiceConfig' => ['prebuiltVoiceConfig' => ['voiceName' => 'Puck']]],
            ]]]);
    }

    #[Test]
    public function audio_requires_a_voice(): void
    {
        $this->expectException(ValidationException::class);

        Gemini::audio()->prompt('Hello')->generate();
    }

    #[Test]
    public function it_generates_embeddings(): void
    {
        Http::fake(['*' => Http::response(['embedding' => ['values' => [0.1, 0.2]]])]);

        $result = Gemini::embeddings(['content' => ['parts' => [['text' => 'hi']]]]);

        $this->assertSame([0.1, 0.2], $result['embedding']['values']);
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/models/gemini-embedding-001:embedContent')
            && $request['model'] === 'models/gemini-embedding-001');
    }
}
