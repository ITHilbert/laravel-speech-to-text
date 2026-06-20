<?php

declare(strict_types=1);

namespace ITHilbert\SpeechToText\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use ITHilbert\SpeechToText\Contracts\TranscriberPort;
use ITHilbert\SpeechToText\Data\AudioInput;
use ITHilbert\SpeechToText\Enums\SpeechMode;
use ITHilbert\SpeechToText\Exceptions\TranscriptionFailedException;

/**
 * Dünner Endpoint: validiert den Upload und delegiert an den Port.
 * Enthält bewusst keine Anbieter-Logik — die liegt in den Adaptern.
 */
final class TranscribeController
{
    public function __construct(private readonly TranscriberPort $transcriber) {}

    public function __invoke(Request $request): JsonResponse
    {
        if ($this->transcriber->mode() === SpeechMode::Client) {
            return response()->json([
                'message' => 'Aktiver Treiber transkribiert im Browser; kein Server-Upload nötig.',
            ], 409);
        }

        $maxKb = (int) config('speech.max_upload_kb', 25_000);

        $validated = Validator::make($request->all(), [
            'audio' => ['required', 'file', 'mimetypes:audio/webm,audio/ogg,audio/wav,audio/mpeg,audio/mp4,video/webm', "max:{$maxKb}"],
            'language' => ['nullable', 'string', 'max:10'],
        ])->validate();

        $file = $request->file('audio');

        try {
            $transcript = $this->transcriber->transcribe(new AudioInput(
                path: $file->getRealPath(),
                language: $validated['language'] ?? 'de-DE',
                mimeType: $file->getMimeType(),
            ));
        } catch (TranscriptionFailedException $e) {
            report($e);

            return response()->json(['message' => 'Transkription fehlgeschlagen.'], 502);
        }

        return response()->json($transcript->toArray());
    }
}
