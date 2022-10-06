<?php declare(strict_types=1);

namespace Xdg\DesktopFile\Internal;

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
        return sprintf(
            "%s[%s]\n%s",
            $this->comment ? Syntax::serializeComment($this->comment) . "\n" : '',
            $this->name,
            implode("\n", $this->entries),
        );
    }
}
