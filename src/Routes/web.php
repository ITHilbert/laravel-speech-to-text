<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use ITHilbert\SpeechToText\Http\Controllers\TranscribeController;

Route::post(config('speech.route.uri', '/speech/transcribe'), TranscribeController::class)
    ->middleware(config('speech.route.middleware', ['web', 'auth']))
    ->name('speech.transcribe');
