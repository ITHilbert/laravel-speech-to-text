<?php

declare(strict_types=1);

namespace ITHilbert\SpeechToText\Contracts;

use ITHilbert\SpeechToText\Data\AudioInput;
use ITHilbert\SpeechToText\Data\Transcript;
use ITHilbert\SpeechToText\Enums\SpeechMode;
use ITHilbert\SpeechToText\Exceptions\TranscriptionFailedException;

/**
 * Der Port, den die Anwendung kennt. Konkrete Engines (Whisper HTTP/CLI,
 * OpenAI, Browser) liegen dahinter und werden per Konfiguration getauscht.
 */
interface TranscriberPort
{
    /**
     * @throws TranscriptionFailedException
     */
    public function transcribe(AudioInput $audio): Transcript;

    /**
     * Server = Audio hochladen und serverseitig transkribieren.
     * Client = Browser erkennt selbst, kein Upload (transcribe() nicht anwendbar).
     */
    public function mode(): SpeechMode;
}
