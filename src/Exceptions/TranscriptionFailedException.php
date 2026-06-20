<?php

declare(strict_types=1);

namespace ITHilbert\SpeechToText\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Projektinterne Exception. Adapter mappen externe Fehler (HTTP, Prozess, API)
 * auf diesen Typ, damit die Anwendung keine anbieterspezifischen Exceptions kennt.
 */
final class TranscriptionFailedException extends RuntimeException
{
    public static function fromDriver(string $driver, string $reason, ?Throwable $previous = null): self
    {
        return new self("Transkription über Treiber '{$driver}' fehlgeschlagen: {$reason}", 0, $previous);
    }

    public static function clientSideDriver(string $driver): self
    {
        return new self("Treiber '{$driver}' transkribiert clientseitig (Browser) — transcribe() ist hier nicht anwendbar.");
    }
}
