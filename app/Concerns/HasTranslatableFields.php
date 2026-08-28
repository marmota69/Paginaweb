<?php

namespace App\Concerns;

/**
 * Content that is authored once per language and stored as a JSON object
 * shaped `{"es": "…", "en": "…"}`. The listed fields must be cast to `array`.
 *
 * Reading always resolves to a usable string: the requested locale first, then
 * Spanish, then English — so a half-translated record never renders a blank.
 */
trait HasTranslatableFields
{
    /**
     * Read a translatable field in the given locale, falling back sensibly.
     */
    public function t(string $field, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $value = $this->getAttribute($field);

        if (! is_array($value)) {
            return (string) $value;
        }

        foreach ([$locale, 'es', 'en'] as $candidate) {
            if (filled($value[$candidate] ?? null)) {
                return (string) $value[$candidate];
            }
        }

        return '';
    }

    /**
     * Read a translatable field split into paragraphs on blank lines.
     *
     * @return array<int, string>
     */
    public function paragraphs(string $field, ?string $locale = null): array
    {
        return collect(preg_split('/\R{2,}/', $this->t($field, $locale)) ?: [])
            ->map(fn (string $paragraph) => trim($paragraph))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Normalise a translatable field to `['es' => …, 'en' => …]` for editing.
     *
     * @return array{es: string, en: string}
     */
    public function translations(string $field): array
    {
        $value = $this->getAttribute($field);

        if (! is_array($value)) {
            return ['es' => (string) $value, 'en' => (string) $value];
        }

        return [
            'es' => (string) ($value['es'] ?? ''),
            'en' => (string) ($value['en'] ?? ''),
        ];
    }
}
