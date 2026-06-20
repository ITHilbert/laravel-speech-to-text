<?php

declare(strict_types=1);

namespace ITHilbert\SpeechToText\Adapters;

use ITHilbert\SpeechToText\Contracts\TranscriberPort;
use ITHilbert\SpeechToText\Data\AudioInput;
use ITHilbert\SpeechToText\Data\Transcript;
use ITHilbert\SpeechToText\Enums\SpeechMode;
use ITHilbert\SpeechToText\Exceptions\TranscriptionFailedException;

/**
 * Option A: Die Spracherkennung läuft im Browser (Web Speech API).
 * Am Server gibt es nichts zu transkribieren — der Modus signalisiert dem
 * Frontend, dass es selbst erkennen soll. transcribe() ist hier ein Fehler.
 */
final class BrowserTranscriberAdapter implements TranscriberPort
{
    public function transcribe(AudioInput $audio): Transcript
    {
        throw TranscriptionFailedException::clientSideDriver('browser');
    }

    public function mode(): SpeechMode
    {
        return SpeechMode::Client;
    }
}
