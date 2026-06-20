<?php

declare(strict_types=1);

namespace ITHilbert\SpeechToText\Facades;

use Illuminate\Support\Facades\Facade;
use ITHilbert\SpeechToText\Contracts\TranscriberPort;
use ITHilbert\SpeechToText\Data\AudioInput;
use ITHilbert\SpeechToText\Data\Transcript;
use ITHilbert\SpeechToText\Enums\SpeechMode;
use ITHilbert\SpeechToText\SpeechManager;
use ITHilbert\SpeechToText\Testing\FakeTranscriber;

/**
 * @method static Transcript transcribe(AudioInput $audio)
 * @method static SpeechMode mode()
 * @method static SpeechMode currentMode()
 *
 * @see SpeechManager
 */
final class SpeechToText extends Facade
{
    #[\Override]
    protected static function getFacadeAccessor(): string
    {
        return TranscriberPort::class;
    }

    /**
     * Ersetzt den echten Treiber durch ein Test-Double und gibt es zur Inspektion zurück.
     */
    public static function fake(string $text = 'Testtranskript', SpeechMode $mode = SpeechMode::Server): FakeTranscriber
    {
        $fake = new FakeTranscriber($text, $mode);

        self::$app->instance(TranscriberPort::class, $fake);

        return $fake;
    }
}
