<?php declare(strict_types=1);

namespace Xdg\DesktopFile\Exception;

class SyntaxError extends \RuntimeException
{
    public static function invalidGroupName(string $name): static
    {
        return new static(sprintf(
            'Invalid group name: "%s"',
            $name,
        ));
    }

    public static function invalidKey(string $key): static
    {
        return new static(sprintf(
            'Invalid key: "%s"',
            $key,
        ));
    }

    public static function invalidListSeparator(string $separator): static
    {
        return new static(sprintf(
            'Invalid list separator: %s',
            var_export($separator, true),
        ));
    }

    public static function invalidBoolean(string $value): static
    {
        return new static(sprintf(
            'Invalid boolean value: %s',
            var_export($value, true),
        ));
    }

    public static function invalidInteger(string $value): static
    {
        return new static(sprintf(
            'Invalid integer value: %s',
            var_export($value, true),
        ));
    }

    public static function invalidFloat(string $value): static
    {
        return new static(sprintf(
            'Invalid float value: %s',
            var_export($value, true),
        ));
    }
}
