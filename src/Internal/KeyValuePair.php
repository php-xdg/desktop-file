<?php declare(strict_types=1);

namespace Xdg\DesktopFile\Internal;

/**
 * @internal
 * @todo cache locale lookups
 */
final class KeyValuePair implements \Stringable
{
    /**
     * @var array<string, LocalizedKeyValuePair>
     */
    private array $translations = [];
    public string $comment = '';

    public function __construct(
        public readonly string $key,
        public string $value,
    ) {
    }

    public function setTranslation(string $locale, string $value): void
    {
        $entry = $this->translations[$locale] ??= new LocalizedKeyValuePair($this->key, $locale, '');
        $entry->value = $value;
    }

    public function getTranslation(string $locale): ?string
    {
        return $this->getTranslationEntry($locale)?->value;
    }

    public function getTranslationEntry(string $locale): ?LocalizedKeyValuePair
    {
        return $this->translations[$locale] ?? $this->findTranslation($locale);
    }

    public function removeTranslation(string $locale): void
    {
        if ($entry = $this->getTranslationEntry($locale)) {
            unset($this->translations[$entry->locale]);
        }
    }

    public function __toString(): string
    {
        $entries = [];
        if ($this->comment) {
            $entries[] = Syntax::serializeComment($this->comment);
        }
        $entries[] = sprintf('%s=%s', $this->key, $this->value);

        return implode("\n", array_merge($entries, $this->translations));
    }

    private function findTranslation(string $locale): ?LocalizedKeyValuePair
    {
        if ($key = $this->findTranslationKey($locale)) {
            return $this->translations[$key];
        }

        return null;
    }

    private function findTranslationKey(string $locale): ?string
    {
        // TODO: this can fail if count($this->translations) > 100
        return \Locale::lookup(array_keys($this->translations), $locale);
    }
}
