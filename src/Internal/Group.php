<?php declare(strict_types=1);

namespace Xdg\DesktopFile\Internal;

use Xdg\Locale\Locale;

/**
 * @internal
 */
final class Group implements \Stringable
{
    public string $comment = '';

    /**
     * @param array<string, KeyValuePair> $entries
     */
    public function __construct(
        public readonly string $name,
        public array $entries = [],
    ) {
    }

    public function __toString(): string
    {
        return ($this->comment ? Syntax::serializeComment($this->comment) . "\n" : '')
            . "[$this->name]\n"
            . implode("\n", $this->entries)
        ;
    }

    public function getTranslation(string $key, Locale $locale): ?KeyValuePair
    {
        foreach ($locale->getVariants() as $variant) {
            if ($entry = $this->entries["{$key}[{$variant}]"] ?? null) {
                return $entry;
            }
        }

        return null;
    }
}
