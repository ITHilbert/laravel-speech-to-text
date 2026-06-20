# ITHilbert Speech-to-Text

Laravel-Package für austauschbare Sprache-zu-Text-Transkription (Diktieren) hinter einem
Port/Adapter. Der aktive Treiber wird per `.env` umgeschaltet — die Anwendung bleibt unberührt.

## Treiber

| Treiber        | Bedeutung                                          | Modus  |
|----------------|----------------------------------------------------|--------|
| `whisper_http` | Self-hosted Whisper über OpenAI-kompatible HTTP-API (z. B. `faster-whisper-server`) | server |
| `whisper_cli`  | Self-hosted Whisper als lokales Binary (`whisper.cpp`) | server |
| `openai`       | OpenAI Cloud-API (höchste Genauigkeit, Audio verlässt das Haus) | server |
| `browser`      | Web Speech API direkt im Browser (kein Upload)     | client |

- **server**: Audio wird hochgeladen und serverseitig transkribiert.
- **client**: Der Browser erkennt selbst; `transcribe()` ist serverseitig nicht anwendbar.

## Installation

```bash
composer require ithilbert/speech-to-text
php artisan vendor:publish --tag=speech-config    # optional
php artisan vendor:publish --tag=speech-assets    # Vue-Komponente nach resources/js
```

Service Provider und Facade werden automatisch über Laravel Package-Discovery registriert.
Der Endpunkt `POST /speech/transcribe` (Route-Name `speech.transcribe`, Middleware `web,auth`)
wird automatisch geladen.

## Konfiguration (`.env`)

```env
SPEECH_DRIVER=whisper_http

# whisper_http — OpenAI-kompatibler Server; /audio/transcriptions wird angehängt, /v1 hier mitgeben:
SPEECH_WHISPER_URL="https://whisper.example/v1"
SPEECH_WHISPER_MODEL="Systran/faster-whisper-small"
# SPEECH_WHISPER_TOKEN=...        # nur falls der Server Auth verlangt
# SPEECH_WHISPER_VERIFY=false     # nur für LAN-Dienste mit selbstsigniertem Zertifikat

# whisper_cli
# SPEECH_WHISPER_BIN=/usr/local/bin/whisper-cli
# SPEECH_WHISPER_MODEL_PATH=/opt/whisper/models/ggml-small.bin

# openai
# OPENAI_API_KEY=sk-...
```

## Verwendung (Backend)

```php
use ITHilbert\SpeechToText\Facades\SpeechToText;
use ITHilbert\SpeechToText\Data\AudioInput;

$transcript = SpeechToText::transcribe(new AudioInput($path, 'de-DE'));
$transcript->text;        // erkannter Text
SpeechToText::currentMode(); // SpeechMode::Server | SpeechMode::Client
```

## Verwendung (Frontend)

Die Vue-Komponente `<speech-input>` kennt beide Modi. `mode` aus dem Backend reichen:

```blade
<speech-input
    v-model="text"
    mode="{{ app(\ITHilbert\SpeechToText\SpeechManager::class)->currentMode()->value }}"
    transcribe-url="{{ route('speech.transcribe') }}"
    language="de-DE"
></speech-input>
```

- `mode="server"` → nimmt Audio auf (MediaRecorder) und lädt es an `transcribe-url`.
- `mode="client"` → nutzt die Web Speech API direkt im Browser.

## Tests

```php
use ITHilbert\SpeechToText\Facades\SpeechToText;

$fake = SpeechToText::fake('Diktierter Text');   // ersetzt den echten Treiber
// ... Code ausführen ...
$fake->recorded;   // mitgeschnittene AudioInput-Aufrufe
```

## Architektur

```
TranscriberPort (Contract)          ← die Anwendung kennt nur dies
   ▲
SpeechManager (Treiber-Auswahl)
   ├── WhisperHttpTranscriberAdapter   (whisper_http)
   ├── WhisperCliTranscriberAdapter    (whisper_cli)
   ├── OpenAiTranscriberAdapter        (openai)
   └── BrowserTranscriberAdapter       (browser)
```

Externe Fehler (HTTP, Prozess, API) werden auf `TranscriptionFailedException` gemappt.
