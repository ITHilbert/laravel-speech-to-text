<?php

declare(strict_types=1);

namespace ITHilbert\SpeechToText\Data;

/**
 * Eingabe-DTO für eine Transkription. Kapselt den Pfad zur Audiodatei
 * sowie die gewünschte Sprache, sodass die Adapter primitive Argumente
 * statt Framework-/Request-Objekte erhalten.
 */
final readonly class AudioInput
{
    public function __construct(
        public string $path,
        public string $language = 'de-DE',
        public ?string $mimeType = null,
    ) {}

    /**
     * Zwei-Buchstaben-Sprachcode (z. B. "de") für APIs, die kein Locale akzeptieren.
     */
    public function shortLanguage(): string
    {
        return strtolower(explode('-', $this->language)[0]);
    }
}
