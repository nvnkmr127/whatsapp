<?php

namespace App\Providers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;

class TranslatorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('translator', function ($app) {
            $loader = new FileLoader(
                new Filesystem,
                resource_path('lang')
            );

            // Get stored locale or default
            $locale = Session::get('locale', $app['config']['app.locale']);

            // Create translator instance
            $translator = new Translator($loader, $locale);

            // Add JSON paths
            $this->addJsonPaths($translator);

            return $translator;
        });
    }

    protected function addJsonPaths(Translator $translator): void
    {
        // Default English path
        $translator->addJsonPath(resource_path('lang/en.json'));

        // Add translations path for other languages
        $translator->addJsonPath(resource_path('lang/translations'));
    }
}
