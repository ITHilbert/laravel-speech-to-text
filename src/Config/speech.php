<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Aktiver Treiber
    |--------------------------------------------------------------------------
    | Schaltet die gesamte Spracherkennung um — ohne Änderung am Anwendungscode.
    | Mögliche Werte: whisper_http, whisper_cli, openai, browser
    */
    'driver' => env('SPEECH_DRIVER', 'whisper_http'),

    /*
    |--------------------------------------------------------------------------
    | HTTP-Endpoint, der das Audio entgegennimmt (Frontend)
    |--------------------------------------------------------------------------
    | Route-Name + URI des serverseitigen Transkriptions-Endpunkts. Auf 'false'
    | setzen, um die mitgelieferte Route nicht zu registrieren.
    */
    'route' => [
        'enabled' => env('SPEECH_ROUTE_ENABLED', true),
        'uri' => env('SPEECH_ROUTE_URI', '/speech/transcribe'),
        'middleware' => ['web', 'auth'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Upload-Grenzen
    |--------------------------------------------------------------------------
    */
    'max_upload_kb' => env('SPEECH_MAX_UPLOAD_KB', 25_000),

    /*
    |--------------------------------------------------------------------------
    | Treiber-Konfiguration
    |--------------------------------------------------------------------------
    */
    'drivers' => [
        'whisper_http' => [
            // OpenAI-kompatible Whisper-API (z. B. faster-whisper-server). Der
            // Pfad /audio/transcriptions wird angehängt, /v1 also in der URL mitgeben.
            // Konkrete URL/Modell gehören in die .env des jeweiligen Projekts,
            // damit das Package projekt-/umgebungsunabhängig bleibt.
            'endpoint' => env('SPEECH_WHISPER_URL'),
            'model' => env('SPEECH_WHISPER_MODEL'),
            'token' => env('SPEECH_WHISPER_TOKEN'),
            'timeout' => (int) env('SPEECH_WHISPER_TIMEOUT', 120),
            'response_text_key' => env('SPEECH_WHISPER_TEXT_KEY', 'text'),
            // TLS-Zertifikat prüfen. Für LAN-Dienste mit selbstsigniertem Zertifikat
            // ggf. auf false setzen (nur im internen Netz vertretbar).
            'verify' => filter_var(env('SPEECH_WHISPER_VERIFY', true), FILTER_VALIDATE_BOOL),
        ],

        'whisper_cli' => [
            // Binary- und Modellpfad sind umgebungsspezifisch → in der .env setzen.
            'binary' => env('SPEECH_WHISPER_BIN'),
            'model_path' => env('SPEECH_WHISPER_MODEL_PATH'),
            'timeout' => (int) env('SPEECH_WHISPER_TIMEOUT', 120),
        ],

        'openai' => [
            'key' => env('OPENAI_API_KEY'),
            'model' => env('SPEECH_OPENAI_MODEL', 'whisper-1'),
            'base_uri' => env('OPENAI_BASE_URI', 'https://api.openai.com/v1'),
            'timeout' => (int) env('SPEECH_OPENAI_TIMEOUT', 120),
        ],

        'browser' => [
            // Läuft im Client (Web Speech API) — keine serverseitige Konfiguration nötig.
        ],
    ],
];
