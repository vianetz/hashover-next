<?php
declare(strict_types=1);

namespace HashOver\Domain;

use HashOver\Setup;

final class Translator
{
    private Setup $setup;
    /** @var array<string,string> */
    private array $translations = [];

    public function __construct(Setup $setup)
    {
        $this->setup = $setup;

        $localeFilePath = $this->getLocaleFile();
        $this->includeLocaleFile($localeFilePath);
    }

    public function translate(?string $textIdentifier = null): string
    {
        return $this->translations[$textIdentifier] ?? '';
    }

    /**
     * Adds optionality to any given locale string
     */
    public function optionalize(string $key, string $choice = 'optional'): string
    {
        return $this->optionality($this->translations[$key] . ' (%s)', $choice);
    }

    private function getLocaleFile(): string
    {
        // Check if we are automatically selecting the locale
        if ($this->setup->language === 'auto') {
            // If so, get system locale
            $ctype = setlocale(LC_CTYPE, 0);

            // Split locale by encoding
            $ctype_parts = explode('.', $ctype);

            // Get locale code (en_US, de_DE, etc.)
            $locale = $ctype_parts[0];
        } else {
            // If not, use configured language as locale
            $locale = $this->setup->language;
        }

        $locale = mb_strtolower($locale);

        $localesPath = APP_DIR . '/locales';

        // Convert locale code to dashed format (en-us, de-de, etc.)
        if (strpos($locale, '_') !== false) {
            $locale = str_replace('_', '-', $locale);
        }

        // Convert locale code to dashed format if it isn't hyphenated
        if (strpos($locale, '-') === false) {
            $locale .= '-' . $locale;
        }

        // Locale file path to try
        $locale_file = $localesPath . '/' . $locale . '.php';

        // Try to use locale file for current locale
        if (file_exists($locale_file)) {
            // If exists, set locale code as language setting
            $this->setup->language = $locale;

            // And return locale file path
            return $locale_file;
        }

        // Otherwise, set language setting to English
        $this->setup->language = 'en-us';

        // And return path to English locale
        return $localesPath . '/en-us.php';
    }

    /**
     * @throws \Exception
     */
    private function includeLocaleFile(string $file): void
    {
        $locale = [];
        if (! @include($file)) {
            throw new \Exception(sprintf(
                '%s locale file could not be included!',
                mb_strtoupper($this->setup->language)
            ));
        }

        $this->translations = $locale;
    }

    /**
     * Prepares locale by modifying them in various ways
     */
    protected function prepareLocale(): void
    {
        // Add optionality to form field title locales
        foreach ($this->setup->formFields as $field => $option) {
            $tooltip_key = $field . '-tip';

            $tooltip_locale = $this->translations[$tooltip_key];

            // Update the locale
            $this->translations[$tooltip_key] = $this->optionality($tooltip_locale, $option);
        }
    }

    /**
     * Injects optionality into a given locale string
     */
    protected function optionality(string $locale, string $choice = 'optional'): string
    {
        $key = $choice === 'required' ? 'required' : 'optional';

        $optionality = mb_strtolower($this->translations[$key]);

        return sprintf($locale, $optionality);
    }
}