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

    public function getTranslations(): array
    {
        return $this->translations;
    }

    public function setTranslation(Locale|string $locale, string $value): void
    {
        $entry = $this->translations[(string)$locale] ??= new LocalizedKeyValuePair($this->key, (string)$locale, '');
        $entry->value = $value;
    }

    public function getTranslation(Locale|string $locale): ?string
    {
        return $this->getTranslationEntry($locale)?->value;
    }

    public function getTranslationEntry(Locale|string $locale): ?LocalizedKeyValuePair
    {
        $locale = Locale::of($locale);

        return $this->translations[(string)$locale] ?? $this->findTranslation($locale);
    }

    public function removeTranslation(Locale|string $locale): void
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

    private function findTranslation(Locale $locale): ?LocalizedKeyValuePair
    {
        if ($key = $locale->select($this->translations)) {
            return $this->translations[$key];
        }

        return null;
    }
}
