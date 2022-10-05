<?php declare(strict_types=1);

namespace Xdg\DesktopEntry\KeyFile\Internal;

use Xdg\DesktopEntry\KeyFile\Exception\ParseError;
use Xdg\DesktopEntry\KeyFile\Exception\SyntaxError;

final class Syntax
{
    public static function validateGroupName(string $name): void
    {
        if (strcspn($name, "[]\n\r") !== \strlen($name)) {
            throw SyntaxError::invalidGroupName($name);
        }
    }

    public static function validateKey(string $name): void
    {
        if (strcspn($name, "[]=\n\r") !== \strlen($name)) {
            throw SyntaxError::invalidKey($name);
        }
    }

    public static function validateListSeparator(string $separator): void
    {
        if ($separator === "\\" || \strlen($separator) !== 1) {
            throw SyntaxError::invalidListSeparator($separator);
        }
    }

    private const GROUP_HEADER_RX = <<<'REGEXP'
    /^ \[ (?<name> [^\[\]]+ ) ] $/x
    REGEXP;

    /**
     * @return string[]
     */
    public static function splitLines(string $input): array
    {
        return preg_split('/(*BSR_ANYCRLF)\R/', $input);
    }

    public static function parseGroupHeader(string $line, int $lineno = 0): string
    {
        if (preg_match(self::GROUP_HEADER_RX, $line, $m)) {
            return trim($m['name']);
        }
        throw ParseError::invalidGroupHeader($line, $lineno);
    }

    private const ENTRY_RX = <<<'REGEXP'
    /^
        (?<key> [^=\[\]\s]+ )
        (?: \[ (?<locale> [\w.@-]+ ) ] )?
        \s* = \s*
        (?<value> .* )
    $/x
    REGEXP;

    /**
     * @return array{string, string, ?string}
     */
    public static function parseKeyValuePair(string $line, int $lineno = 0): array
    {
        if (preg_match(self::ENTRY_RX, $line, $m)) {
            return [$m['key'], $m['value'] ?? '', $m['locale']];
        }

        throw new ParseError(sprintf('Invalid entry: "%s" on line %d', $line, $lineno));
    }

    public static function parseValueAsString(string $value, ?string $listSeparator = null): array|string
    {
        $asList = !!$listSeparator;
        $list = $asList ? [] : null;
        $i = 0;
        $l = \strlen($value);
        $buf = '';
        while ($i < $l) {
            switch ($value[$i] ?? '') {
                case '':
                    break;
                case $listSeparator:
                    if ($asList) {
                        $list[] = $buf;
                        $buf = '';
                    } else {
                        $buf .= $listSeparator;
                    }
                    $i++;
                    break;
                case '\\':
                    $buf .= match ($next = $value[++$i] ?? '') {
                        '', '\\' => '\\',
                        's' => ' ',
                        'n' => "\n",
                        'r' => "\r",
                        't' => "\t",
                        default => '\\' . $next,
                    };
                    $i++;
                    break;
                default:
                    $s = strcspn($value, "\\{$listSeparator}", $i);
                    $buf .= substr($value, $i, $s);
                    $i += $s;
                    break;
            }
        }

        if ($asList && $buf !== '') {
            $list[] = $buf;
        }

        return $asList ? ($list ?? []) : $buf;
    }

    public static function parseBoolean(string $value): bool
    {
        return match ($value) {
            'true' => true,
            'false' => false,
            default => throw SyntaxError::invalidBoolean($value),
        };
    }

    public static function parseInteger(string $value): int
    {
        return match (true) {
            is_numeric($value) => (int)$value,
            default => throw SyntaxError::invalidInteger($value),
        };
    }

    public static function parseFloat(string $value): float
    {
        return match (true) {
            is_numeric($value) => (float)$value,
            default => throw SyntaxError::invalidFloat($value),
        };
    }

    public static function serializeString(string $value, ?string $listSeparator = null): string
    {
        $head = '';
        // escape leading whitespace
        if ($l = strspn($value, " \t")) {
            $head = strtr(substr($value, 0, $l), [' ' => '\s', "\t" => '\t']);
        }

        $tail = addcslashes(substr($value, $l), "\n\t\r\\{$listSeparator}");

        return $head . $tail;
    }

    public static function serializeStringList(array $values, string $listSeparator): string
    {
        return implode($listSeparator, array_map(
            fn(string $v) => self::serializeString($v, $listSeparator),
            $values,
        ));
    }

    public static function serializeComment(string $value): string
    {
        $lines = array_map(
            fn($line) => '#' . $line,
            self::splitLines(rtrim($value)),
        );

        return implode("\n", $lines);
    }
}
