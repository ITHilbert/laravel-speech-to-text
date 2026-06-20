<?php

declare(strict_types=1);

namespace ITHilbert\SpeechToText\Adapters;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use ITHilbert\SpeechToText\Contracts\TranscriberPort;
use ITHilbert\SpeechToText\Data\AudioInput;
use ITHilbert\SpeechToText\Data\Transcript;
use ITHilbert\SpeechToText\Enums\SpeechMode;
use ITHilbert\SpeechToText\Exceptions\TranscriptionFailedException;
use Throwable;

/**
 * Option C-1: Self-hosted Whisper über eine OpenAI-kompatible HTTP-API
 * (z. B. faster-whisper-server / whisper.cpp-server auf dem HomeServer).
 * Standardbetrieb, DSGVO-konform — das Audio verlässt die eigene Infrastruktur nicht.
 *
 * Erwartet den Endpunkt `POST {endpoint}/audio/transcriptions` (OpenAI-Schema)
 * mit Multipart-Feld `file` und der Antwort `{ "text": "..." }`.
 *
 * @phpstan-type WhisperResponse array{text?: string, language?: string, duration?: float}
 */
final class WhisperHttpTranscriberAdapter implements TranscriberPort
{
    /**
     * @param  array{endpoint: string, model?: string, token?: string|null, timeout?: int, response_text_key?: string}  $config
     */
    public function __construct(
        private readonly HttpFactory $http,
        private readonly array $config,
    ) {}

    public function transcribe(AudioInput $audio): Transcript
    {
        $textKey = $this->config['response_text_key'] ?? 'text';

        try {
            $request = $this->http
                ->timeout($this->config['timeout'] ?? 120)
                ->attach('file', file_get_contents($audio->path), basename($audio->path));

            // LAN-Dienste mit selbstsigniertem Zertifikat: TLS-Prüfung abschaltbar.
            if (array_key_exists('verify', $this->config) && $this->config['verify'] === false) {
                $request = $request->withoutVerifying();
            }

            // Optionaler Bearer-Token, falls der Server (anders als faster-whisper-server) Auth verlangt.
            if (! empty($this->config['token'])) {
                $request = $request->withToken($this->config['token']);
            }

            $payload = ['language' => $audio->shortLanguage()];
            if (! empty($this->config['model'])) {
                $payload['model'] = $this->config['model'];
            }

            $response = $request->post($this->transcriptionUrl(), $payload);
        } catch (ConnectionException $e) {
            throw TranscriptionFailedException::fromDriver('whisper_http', $e->getMessage(), $e);
        } catch (Throwable $e) {
            throw TranscriptionFailedException::fromDriver('whisper_http', $e->getMessage(), $e);
        }

        if ($response->failed()) {
            throw TranscriptionFailedException::fromDriver(
                'whisper_http',
                "HTTP {$response->status()}: {$response->body()}",
            );
        }

        $data = $response->json();

        if (! is_array($data) || ! isset($data[$textKey]) || ! is_string($data[$textKey])) {
            throw TranscriptionFailedException::fromDriver(
                'whisper_http',
                "Antwort enthält kein Textfeld '{$textKey}'.",
            );
        }

        return new Transcript(
            text: trim($data[$textKey]),
            language: isset($data['language']) && is_string($data['language']) ? $data['language'] : null,
            durationSeconds: isset($data['duration']) ? (float) $data['duration'] : null,
        );
    }

    public function mode(): SpeechMode
    {
        return SpeechMode::Server;
    }

    /**
     * Baut die Transkriptions-URL. `endpoint` darf mit oder ohne `/v1`-Suffix
     * konfiguriert sein — der Pfad `/audio/transcriptions` wird sicher angehängt.
     */
    private function transcriptionUrl(): string
    {
        if (empty($this->config['endpoint'])) {
            throw TranscriptionFailedException::fromDriver(
                'whisper_http',
                'Kein Endpoint konfiguriert. SPEECH_WHISPER_URL in der .env setzen.',
            );
        }

        $base = rtrim($this->config['endpoint'], '/');

        if (! str_ends_with($base, '/audio/transcriptions')) {
            $base .= '/audio/transcriptions';
        }

        return $base;
    }
}
