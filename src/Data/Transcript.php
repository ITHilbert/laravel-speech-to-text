<?php

declare(strict_types=1);

namespace ITHilbert\SpeechToText\Data;

/**
 * Ausgabe-DTO einer Transkription. Vereinheitlicht die unterschiedlichen
 * Antwortformate der Anbieter auf einen internen Typ.
 */
final readonly class Transcript
{
    public function __construct(
        public string $text,
        public ?string $language = null,
        public ?float $durationSeconds = null,
    ) {}

    /**
     * @return array{text: string, language: string|null, duration_seconds: float|null}
     */
    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'language' => $this->language,
            'duration_seconds' => $this->durationSeconds,
        ];
    }
}
