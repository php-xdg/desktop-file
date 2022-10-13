<?php declare(strict_types=1);

namespace Xdg\DesktopFile;

use Traversable;
use Xdg\DesktopFile\Exception\ParseError;
use Xdg\DesktopFile\Exception\SyntaxError;
use Xdg\DesktopFile\Internal\Group;
use Xdg\DesktopFile\Internal\KeyValuePair;
use Xdg\DesktopFile\Internal\Syntax;
use Xdg\Locale\Locale;

final class DesktopFile implements DesktopFileInterface, \IteratorAggregate
{
    /**
     * @var array<string, Group>
     */
    private array $groups = [];
    private string $listSeparator = ';';
    private string $startComment = '';
    private string $endComment = '';

    /**
     * @var array<string, Locale>
     */
    private array $locales = [];

    public static function parse(
        string $buffer,
        Locale|string|false|null $locale = null,
        bool $keepComments = true,
    ): DesktopFileInterface {
        $self = new self();
        $keepLocales = $locale !== false;
        if ($keepLocales) {
            $locale = $self->getLocale($locale);
        }

        $currentGroup = null;
        $currentComment = '';

        foreach (Syntax::splitLines($buffer) as $lineno => $line) {
            $line = trim($line);
            switch ($line[0] ?? '') {
                case '':
                    if ($keepComments && $currentComment) {
                        $currentComment .= "\n";
                    }
                    break;
                case '#':
                    if ($keepComments) {
                        $currentComment .= substr($line, 1) . "\n";
                    }
                    break;
                case '[':
                    $currentGroup = Syntax::parseGroupHeader($line, $lineno);
                    $self->addGroup($currentGroup, $currentComment);
                    $currentComment = '';
                    break;
                default:
                    if (!$currentGroup) {
                        throw ParseError::missingGroupHeader();
                    }
                    [$key, $value, $_locale] = Syntax::parseKeyValuePair($line, $lineno);
                    if (
                        $_locale && (
                            !$keepLocales
                            || ($locale && !$locale->matches($_locale))
                        )
                    ) {
                        $currentComment = '';
                        break;
                    }
                    $self->setValueUnchecked($currentGroup, $key, $value, $_locale, $currentComment ?: null);
                    $currentComment = '';
                    break;
            }
        }

        if ($currentComment) {
            $self->endComment = $currentComment;
        }

        return $self;
    }

    public function setListSeparator(string $separator): void
    {
        Syntax::validateListSeparator($separator);
        $this->listSeparator = $separator;
    }

    public function getListSeparator(): string
    {
        return $this->listSeparator;
    }

    public function hasGroup(string $groupName): bool
    {
        return isset($this->groups[$groupName]);
    }

    public function getGroups(): array
    {
        return array_keys($this->groups);
    }

    public function getStartGroup(): ?string
    {
        return match ($group = array_key_first($this->groups)) {
            null => null,
            default => (string)$group,
        };
    }

    public function removeGroup(string $groupName): void
    {
        unset($this->groups[$groupName]);
    }

    public function hasKey(string $groupName, string $key): bool
    {
        return isset($this->groups[$groupName]->entries[$key]);
    }

    public function getKeys(string $groupName): array
    {
        if ($group = $this->groups[$groupName] ?? null) {
            return array_keys($group->entries);
        }

        return [];
    }

    public function removeKey(string $groupName, string $key): void
    {
        unset($this->groups[$groupName]->entries[$key]);
    }

    public function resolveLocaleForKey(string $groupName, string $key, Locale|string $locale): ?string
    {
        return $this->getEntry($groupName, $key, $locale)?->locale;
    }

    public function getValue(string $groupName, string $key, Locale|string|null $locale = null): ?string
    {
        if ($locale) {
            $entry = $this->getEntry($groupName, $key, $locale) ?? $this->getEntry($groupName, $key);
            return $entry?->value;
        }

        return $this->getEntry($groupName, $key)?->value;
    }

    public function setValue(string $groupName, string $key, string $value, Locale|string|null $locale = null): void
    {
        Syntax::validateGroupName($groupName);
        Syntax::validateKey($key);
        $this->setValueUnchecked($groupName, $key, $value, $this->getLocale($locale));
    }

    public function getString(string $groupName, string $key, Locale|string|null $locale = null): ?string
    {
        return match ($value = $this->getValue($groupName, $key, $locale)) {
            null => null,
            default => Syntax::parseValueAsString($value),
        };
    }

    public function setString(string $groupName, string $key, string $value, Locale|string|null $locale = null): void
    {
        $this->setValue($groupName, $key, Syntax::serializeString($value), $locale);
    }

    public function getStringList(string $groupName, string $key, Locale|string|null $locale = null): ?array
    {
        return match ($value = $this->getValue($groupName, $key, $locale)) {
            null => null,
            '' => [],
            default => Syntax::parseValueAsString($value, $this->listSeparator),
        };
    }

    public function setStringList(string $groupName, string $key, array $list, Locale|string|null $locale = null): void
    {
        $this->setValue($groupName, $key, Syntax::serializeStringList($list, $this->listSeparator), $locale);
    }

    public function getBoolean(string $groupName, string $key): ?bool
    {
        return match ($value = $this->getValue($groupName, $key)) {
            'true' => true,
            'false' => false,
            null => null,
            default => throw SyntaxError::invalidBoolean($value),
        };
    }

    public function setBoolean(string $groupName, string $key, bool $value): void
    {
        $this->setValue($groupName, $key, $value ? 'true' : 'false');
    }

    public function getBooleanList(string $groupName, string $key): ?array
    {
        return $this->getTypedList($groupName, $key, Syntax::parseBoolean(...));
    }

    public function setBooleanList(string $groupName, string $key, array $values): void
    {
        $this->setTypedList($groupName, $key, $values, fn(bool $v) => $v ? 'true' : 'false');
    }

    public function getInteger(string $groupName, string $key): ?int
    {
        return match ($value = $this->getValue($groupName, $key)) {
            null => null,
            default => Syntax::parseInteger($value),
        };
    }

    public function setInteger(string $groupName, string $key, int $value): void
    {
        $this->setValue($groupName, $key, (string)$value);
    }

    public function getIntegerList(string $groupName, string $key): ?array
    {
        return $this->getTypedList($groupName, $key, Syntax::parseInteger(...));
    }

    public function setIntegerList(string $groupName, string $key, array $values): void
    {
        $this->setTypedList($groupName, $key, $values, fn(int $n) => (string)$n);
    }

    public function getFloat(string $groupName, string $key): ?float
    {
        return match ($value = $this->getValue($groupName, $key)) {
            null => null,
            default => Syntax::parseFloat($value),
        };
    }

    public function setFloat(string $groupName, string $key, float $value): void
    {
        $this->setValue($groupName, $key, (string)$value);
    }

    public function getFloatList(string $groupName, string $key): ?array
    {
        return $this->getTypedList($groupName, $key, Syntax::parseFloat(...));
    }

    public function setFloatList(string $groupName, string $key, array $values): void
    {
        $this->setTypedList($groupName, $key, $values, fn(float $n) => (string)$n);
    }

    public function getStartComment(): string
    {
        return $this->startComment;
    }

    public function setStartComment(string $comment): void
    {
        $this->startComment = $comment;
    }

    public function getEndComment(): string
    {
        return $this->endComment;
    }

    public function setEndComment(string $comment): void
    {
        $this->endComment = $comment;
    }

    public function getGroupComment(string $groupName): ?string
    {
        return $this->groups[$groupName]->comment ?? null;
    }

    public function setGroupComment(string $groupName, string $comment): void
    {
        if ($group = $this->groups[$groupName] ?? null) {
            $group->comment = $comment;
        }
    }

    public function getKeyComment(
        string $groupName,
        string $key,
        Locale|string|null $locale = null
    ): ?string {
        return $this->getEntry($groupName, $key, $locale)?->comment;
    }

    public function setKeyComment(
        string $groupName,
        string $key,
        string $comment,
        Locale|string|null $locale = null
    ): void {
        if ($entry = $this->getEntry($groupName, $locale ? "{$key}[{$locale}]" : $key)) {
            $entry->comment = $comment;
        }
    }

    public function __toString(): string
    {
        $parts = [];
        if ($this->startComment) {
            $parts[] = Syntax::serializeComment($this->startComment);
        }
        $parts[] = implode("\n\n", $this->groups);
        if ($this->endComment) {
            $parts[] = Syntax::serializeComment($this->endComment);
        }

        return implode("\n\n", $parts) . "\n";
    }

    public function getIterator(): Traversable
    {
        foreach ($this->groups as $groupName => $group) {
            foreach ($group->entries as $key => $entry) {
                yield [(string)$groupName, (string)$key, $entry->value];
            }
        }
    }

    private function setValueUnchecked(
        string $groupName,
        string $key,
        string $value,
        Locale|string|null $locale,
        ?string $comment = null,
    ): void {
        $group = $this->groups[$groupName] ??= new Group($groupName);
        $k = $locale ? "{$key}[{$locale}]" : $key;
        $entry = $group->entries[$k] ??= new KeyValuePair($key, $value, $locale ? (string)$locale : null);
        $entry->value = $value;
        if ($comment) {
            $entry->comment = $comment;
        }
    }

    private function addGroup(string $groupName, ?string $comment = null): void
    {
        if ($group = $this->groups[$groupName] ?? null) {
            $group->comment .= $comment ? "\n" . $comment : '';
            return;
        }

        $this->groups[$groupName] = new Group($groupName, $comment ?: '');
    }

    private function getEntry(string $groupName, string $key, Locale|string|null $locale = null): ?KeyValuePair
    {
        if ($group = $this->groups[$groupName] ?? null) {
            if ($locale) {
                return $group->getTranslation($key, $this->getLocale($locale));
            }
            return $group->entries[$key] ?? null;
        }

        return null;
    }

    private function getLocale(Locale|string|null $locale): ?Locale
    {
        return match (true) {
            \is_string($locale) => $this->locales[$locale] ??= Locale::of($locale),
            \is_null($locale) => null,
            default => $locale,
        };
    }

    /**
     * @template T
     *
     * @param string $groupName
     * @param string $key
     * @param callable(string):T $cast
     * @return T[]|null
     */
    private function getTypedList(string $groupName, string $key, callable $cast): ?array
    {
        return match ($rawValue = $this->getValue($groupName, $key)) {
            null => null,
            '' => [],
            default => array_map($cast, Syntax::parseValueAsString($rawValue, $this->listSeparator)),
        };
    }

    /**
     * @template T
     * @param string $groupName
     * @param string $key
     * @param T[] $values
     * @param callable(string):T $cast
     */
    private function setTypedList(string $groupName, string $key, array $values, callable $cast): void
    {
        $rawValue = implode($this->listSeparator, array_map($cast, $values));
        $this->setValue($groupName, $key, $rawValue);
    }
}
