<?php

declare(strict_types=1);

namespace ITHilbert\SpeechToText;

use Illuminate\Support\ServiceProvider;
use ITHilbert\SpeechToText\Contracts\TranscriberPort;

class SpeechToTextServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (config('speech.route.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/Routes/web.php');
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/Config/speech.php' => config_path('speech.php'),
            ], 'speech-config');

            $this->publishes([
                __DIR__.'/Resources/js' => resource_path('js/vendor/speech-to-text'),
            ], 'speech-assets');
        }
    }

    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/Config/speech.php', 'speech');

        $this->app->singleton(SpeechManager::class, fn ($app) => new SpeechManager($app));

        // Der Port zeigt auf den Manager, der den konfigurierten Treiber wählt.
        $this->app->singleton(TranscriberPort::class, fn ($app) => $app->make(SpeechManager::class));
    }
}
