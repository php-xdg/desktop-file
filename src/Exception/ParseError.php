<?php declare(strict_types=1);

namespace Xdg\DesktopFile\Exception;

final class ParseError extends SyntaxError
{
    public static function invalidGroupHeader(string $value, int $lineno): self
    {
        return new self(sprintf(
            'Invalid group header: "%s" on line %d',
            $value,
            $lineno,
        ));
    }

    public static function missingGroupHeader(): self
    {
        return new self('Missing group header');
    }

    public static function invalidEntry(string $entry, int $lineno): self
    {
        return new self(sprintf('Invalid entry: "%s" on line %d', $entry, $lineno));
    }
}
