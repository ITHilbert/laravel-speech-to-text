<?php

declare(strict_types=1);

namespace ITHilbert\SpeechToText\Testing;

use ITHilbert\SpeechToText\Contracts\TranscriberPort;
use ITHilbert\SpeechToText\Data\AudioInput;
use ITHilbert\SpeechToText\Data\Transcript;
use ITHilbert\SpeechToText\Enums\SpeechMode;

/**
 * Test-Double für die Fake-Pflicht: SpeechToText::fake('...') ersetzt den
 * echten Treiber, damit Tests ohne Whisper/OpenAI auskommen.
 */
final class FakeTranscriber implements TranscriberPort
{
    /** @var list<AudioInput> */
    public array $recorded = [];

    public function __construct(
        private readonly string $fakeText = 'Testtranskript',
        private readonly SpeechMode $mode = SpeechMode::Server,
    ) {}

    public function transcribe(AudioInput $audio): Transcript
    {
        $this->recorded[] = $audio;

        return new Transcript(text: $this->fakeText, language: $audio->shortLanguage());
    }

    public function mode(): SpeechMode
    {
        return $this->mode;
    }
}
