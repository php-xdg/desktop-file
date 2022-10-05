<?php declare(strict_types=1);

namespace Xdg\DesktopEntry\KeyFile\Internal;

final class LocalizedKeyValuePair implements \Stringable
{
    public string $comment = '';

    public function __construct(
        public readonly string $key,
        public readonly string $locale,
        public string $value,
    ) {
    }

    public function __toString(): string
    {
        return sprintf(
            '%s%s[%s]=%s',
            $this->comment ? (Syntax::serializeComment($this->comment)) . "\n" : '',
            $this->key,
            $this->locale,
            $this->value,
        );
    }
}
