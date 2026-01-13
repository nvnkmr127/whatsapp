<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Translation\Translator as BaseTranslator;

class Translator extends BaseTranslator
{
    /**
     * Get the translation for the given key.
     *
     * @param  string       $key
     * @param  string|null  $locale
     * @param  bool         $fallback
     * @return string|array
     */
    public function get($key, array $replace = [], $locale = null, $fallback = true)
    {
        $locale = $locale ?: $this->locale;

        // Try to get from cache first
        $cacheKey = "translations.{$locale}.{$key}";

        return Cache::remember($cacheKey, 3600, function () use ($key, $replace, $locale, $fallback) {
            // Load JSON translations first
            $line = $this->getJsonTranslation($locale, $key);

            // If no JSON translation found, try PHP files
            if (is_null($line)) {
                $line = $this->getPhpTranslation($key, $replace, $locale, $fallback);
            }

            // If still no translation and fallback is enabled
            if (is_null($line) && $fallback && $locale !== 'en') {
                $line = $this->get($key, $replace, 'en', false);
            }

            return $this->makeReplacements($line ?: $key, $replace);
        });
    }

    /**
     * Get translated string from JSON file
     */
    protected function getJsonTranslation(string $locale, string $key)
    {
        $path = $locale === 'en'
            ? resource_path('lang/en.json')
            : resource_path("lang/translations/{$locale}.json");

        if (! File::exists($path)) {
            return null;
        }

        static $translations = [];

        if (! isset($translations[$locale])) {
            $translations[$locale] = json_decode(File::get($path), true) ?? [];
        }

        return $translations[$locale][$key] ?? null;
    }

    /**
     * Get translated string from PHP files
     */
    protected function getPhpTranslation(string $key, array $replace, string $locale, bool $fallback)
    {
        $line = parent::get($key, $replace, $locale, $fallback);

        return $line === $key ? null : $line;
    }

    /**
     * Determine if a translation exists for a given locale.
     *
     * @param  string      $key
     * @param  string|null $locale
     * @param  bool        $fallback
     * @return bool
     */
    public function has($key, $locale = null, $fallback = true)
    {
        return $this->get($key, [], $locale) !== $key;
    }

    /**
     * Get the default locale being used.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set the default locale.
     *
     * @param  string $locale
     * @return void
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }
}
