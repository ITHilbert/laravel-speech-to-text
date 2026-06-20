<?php

declare(strict_types=1);

namespace ITHilbert\SpeechToText\Adapters;

use ITHilbert\SpeechToText\Contracts\TranscriberPort;
use ITHilbert\SpeechToText\Data\AudioInput;
use ITHilbert\SpeechToText\Data\Transcript;
use ITHilbert\SpeechToText\Enums\SpeechMode;
use ITHilbert\SpeechToText\Exceptions\TranscriptionFailedException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Option C-2: Self-hosted Whisper als lokales Binary (z. B. whisper.cpp/whisper-cli).
 * Kein laufender Webserver nötig — gut für Offline-/Batch-Betrieb oder als Fallback.
 */
final class WhisperCliTranscriberAdapter implements TranscriberPort
{
    /**
     * @param  array{binary: string, model_path: string, language?: string, timeout?: int}  $config
     */
    public function __construct(
        private readonly array $config,
    ) {}

    public function transcribe(AudioInput $audio): Transcript
    {
        // whisper.cpp: -otxt schreibt das Ergebnis nach <input>.txt; -nt unterdrückt Zeitstempel.
        $process = new Process([
            $this->config['binary'],
            '-m', $this->config['model_path'],
            '-l', $audio->shortLanguage(),
            '-otxt',
            '-nt',
            '-f', $audio->path,
        ]);
        $process->setTimeout((float) ($this->config['timeout'] ?? 120));

        try {
            $process->run();
        } catch (ProcessTimedOutException $e) {
            throw TranscriptionFailedException::fromDriver('whisper_cli', 'Zeitüberschreitung des Prozesses.', $e);
        } catch (Throwable $e) {
            throw TranscriptionFailedException::fromDriver('whisper_cli', $e->getMessage(), $e);
        }

        if (! $process->isSuccessful()) {
            throw TranscriptionFailedException::fromDriver(
                'whisper_cli',
                trim($process->getErrorOutput()) ?: 'Prozess endete mit Fehlercode.',
            );
        }

        $text = $this->readResult($audio->path, $process->getOutput());

        return new Transcript(
            text: trim($text),
            language: $audio->shortLanguage(),
        );
    }

    public function mode(): SpeechMode
    {
        return SpeechMode::Server;
    }

    /**
     * whisper.cpp legt das Transkript neben der Eingabe als "<input>.txt" ab.
     * Existiert die Datei nicht (andere Build-Variante), nutzen wir STDOUT.
     */
    private function readResult(string $audioPath, string $stdout): string
    {
        $txtPath = $audioPath.'.txt';

        if (is_file($txtPath)) {
            $contents = (string) file_get_contents($txtPath);
            @unlink($txtPath);

            return $contents;
        }

        if (trim($stdout) === '') {
            throw TranscriptionFailedException::fromDriver('whisper_cli', 'Kein Transkript erzeugt.');
        }

        return $stdout;
    }
}
