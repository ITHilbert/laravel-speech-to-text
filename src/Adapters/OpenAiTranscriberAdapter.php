<?php

declare(strict_types=1);

namespace ITHilbert\SpeechToText\Adapters;

use Illuminate\Http\Client\Factory as HttpFactory;
use ITHilbert\SpeechToText\Contracts\TranscriberPort;
use ITHilbert\SpeechToText\Data\AudioInput;
use ITHilbert\SpeechToText\Data\Transcript;
use ITHilbert\SpeechToText\Enums\SpeechMode;
use ITHilbert\SpeechToText\Exceptions\TranscriptionFailedException;
use Throwable;

/**
 * Option B: OpenAI Audio-Transkription (Whisper als Cloud-Dienst).
 * Höchste Genauigkeit, aber das Audio verlässt die eigene Infrastruktur —
 * bewusst nur als Fallback einsetzen (DSGVO).
 */
final class OpenAiTranscriberAdapter implements TranscriberPort
{
    /**
     * @param  array{key: string, model?: string, base_uri?: string, timeout?: int}  $config
     */
    public function __construct(
        private readonly HttpFactory $http,
        private readonly array $config,
    ) {}

    public function transcribe(AudioInput $audio): Transcript
    {
        $baseUri = rtrim($this->config['base_uri'] ?? 'https://api.openai.com/v1', '/');

        try {
            $response = $this->http
                ->withToken($this->config['key'])
                ->timeout($this->config['timeout'] ?? 120)
                ->attach('file', file_get_contents($audio->path), basename($audio->path))
                ->post($baseUri.'/audio/transcriptions', [
                    'model' => $this->config['model'] ?? 'whisper-1',
                    'language' => $audio->shortLanguage(),
                ]);
        } catch (Throwable $e) {
            throw TranscriptionFailedException::fromDriver('openai', $e->getMessage(), $e);
        }

        if ($response->failed()) {
            throw TranscriptionFailedException::fromDriver(
                'openai',
                "HTTP {$response->status()}: {$response->body()}",
            );
        }

        $text = $response->json('text');

        if (! is_string($text)) {
            throw TranscriptionFailedException::fromDriver('openai', 'Antwort enthält kein Textfeld.');
        }

        return new Transcript(text: trim($text));
    }

    public function mode(): SpeechMode
    {
        return SpeechMode::Server;
    }
}
