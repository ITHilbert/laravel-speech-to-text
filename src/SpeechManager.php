<?php

declare(strict_types=1);

namespace ITHilbert\SpeechToText;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Manager;
use ITHilbert\SpeechToText\Adapters\BrowserTranscriberAdapter;
use ITHilbert\SpeechToText\Adapters\OpenAiTranscriberAdapter;
use ITHilbert\SpeechToText\Adapters\WhisperCliTranscriberAdapter;
use ITHilbert\SpeechToText\Adapters\WhisperHttpTranscriberAdapter;
use ITHilbert\SpeechToText\Contracts\TranscriberPort;
use ITHilbert\SpeechToText\Data\AudioInput;
use ITHilbert\SpeechToText\Data\Transcript;
use ITHilbert\SpeechToText\Enums\SpeechDriver;
use ITHilbert\SpeechToText\Enums\SpeechMode;

/**
 * Wählt anhand der Konfiguration den aktiven Transcriber-Treiber. Delegiert
 * transcribe()/mode() an die gewählte Implementierung, sodass die Anwendung
 * nur diese Fassade kennt und der Treiber per .env tauschbar bleibt.
 */
class SpeechManager extends Manager implements TranscriberPort
{
    public function getDefaultDriver(): string
    {
        return (string) $this->config->get('speech.driver', SpeechDriver::WhisperHttp->value);
    }

    public function transcribe(AudioInput $audio): Transcript
    {
        return $this->driver()->transcribe($audio);
    }

    public function mode(): SpeechMode
    {
        return $this->driver()->mode();
    }

    /**
     * Modus des aktuell konfigurierten Treibers, ohne ihn zu instanziieren —
     * praktisch fürs Frontend (Server-Upload vs. Browser-Erkennung).
     */
    public function currentMode(): SpeechMode
    {
        return SpeechDriver::from($this->getDefaultDriver())->mode();
    }

    protected function createWhisperHttpDriver(): TranscriberPort
    {
        return new WhisperHttpTranscriberAdapter(
            $this->container->make(HttpFactory::class),
            $this->driverConfig('whisper_http'),
        );
    }

    protected function createWhisperCliDriver(): TranscriberPort
    {
        return new WhisperCliTranscriberAdapter($this->driverConfig('whisper_cli'));
    }

    protected function createOpenaiDriver(): TranscriberPort
    {
        return new OpenAiTranscriberAdapter(
            $this->container->make(HttpFactory::class),
            $this->driverConfig('openai'),
        );
    }

    protected function createBrowserDriver(): TranscriberPort
    {
        return new BrowserTranscriberAdapter;
    }

    /**
     * @return array<string, mixed>
     */
    private function driverConfig(string $driver): array
    {
        return (array) $this->config->get("speech.drivers.{$driver}", []);
    }
}
