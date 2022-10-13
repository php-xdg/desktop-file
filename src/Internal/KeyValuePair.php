<?php declare(strict_types=1);

namespace Xdg\DesktopFile\Internal;

/**
 * @internal
 */
final class KeyValuePair implements \Stringable
{
    public string $comment = '';

    public function __construct(
        public readonly string $key,
        public string $value,
        public readonly ?string $locale,
    ) {
    }

    public function __toString(): string
    {
        $output = '';
        if ($this->comment) {
            $output .= Syntax::serializeComment($this->comment) . "\n";
        }
        $key = $this->locale ? "{$this->key}[{$this->locale}]" : $this->key;
        $output .= "{$key}={$this->value}";

        return $output;
    }
}
