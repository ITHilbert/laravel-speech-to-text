<?php

declare(strict_types=1);

namespace ITHilbert\SpeechToText\Enums;

/**
 * Wo die Spracherkennung tatsächlich passiert.
 *
 * Server: Audio wird ans Backend hochgeladen und dort transkribiert (Whisper/OpenAI).
 * Client: Der Browser erkennt selbst (Web Speech API) — es gibt kein Audio am Server.
 */
enum SpeechMode: string
{
    case Server = 'server';
    case Client = 'client';
}
