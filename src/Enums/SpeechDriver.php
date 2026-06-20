<?php

declare(strict_types=1);

namespace ITHilbert\SpeechToText\Enums;

/**
 * Verfügbare Treiber. Der aktive Treiber wird über config('speech.driver')
 * (bzw. SPEECH_DRIVER) gewählt und ist im laufenden Betrieb umschaltbar.
 */
enum SpeechDriver: string
{
    case WhisperHttp = 'whisper_http';
    case WhisperCli = 'whisper_cli';
    case OpenAi = 'openai';
    case Browser = 'browser';

    public function mode(): SpeechMode
    {
        return match ($this) {
            self::Browser => SpeechMode::Client,
            default => SpeechMode::Server,
        };
    }
}
