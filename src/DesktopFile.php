<?php declare(strict_types=1);

namespace Xdg\DesktopFile;

use Traversable;
use Xdg\DesktopFile\Exception\ParseError;
use Xdg\DesktopFile\Exception\SyntaxError;
use Xdg\DesktopFile\Internal\Group;
use Xdg\DesktopFile\Internal\KeyValuePair;
use Xdg\DesktopFile\Internal\Locale;
use Xdg\DesktopFile\Internal\Syntax;

final class DesktopFile implements DesktopFileInterface, \IteratorAggregate
{
    /**
     * @var array<string, Group>
     */
    private array $groups = [];
    private string $listSeparator = ';';
    private string $comment = '';
    /**
     * @var array<string, Locale>
     */
    private array $locales = [];

    public static function parse(
        string $buffer,
        ?string $locale = null,
        bool $keepComments = false,
    ): DesktopFileInterface {
        $self = new self();
        if ($locale) {
            $locale = $self->locales[$locale] = Locale::of($locale);
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
                    $group = $self->groups[$currentGroup] ??= new Group($currentGroup);
                    $group->comment = $currentComment;
                    $currentComment = '';
                    break;
                default:
                    if (!$currentGroup) {
                        throw ParseError::missingGroupHeader();
                    }
                    [$key, $value, $_locale] = Syntax::parseKeyValuePair($line, $lineno);
                    if ($_locale && $locale && !$locale->matches($_locale)) {
                        break;
                    }
                    $self->setValueUnchecked($currentGroup, $key, $value, $_locale);
                    if ($currentComment) {
                        $self->setKeyComment($currentGroup, $key, $currentComment, $_locale);
                        $currentComment = '';
                    }
                    break;
            }
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
        return array_key_first($this->groups);
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
        if ($group = $this->groups[$groupName]) {
            return array_keys($group->entries);
        }

        return [];
    }

    public function removeKey(string $groupName, string $key, ?string $locale = null): void
    {
        if ($locale) {
            if ($entry = $this->groups[$groupName]->entries[$key] ?? null) {
                $entry->removeTranslation($locale);
            }
            return;
        }

        unset($this->groups[$groupName]->entries[$key]);
    }

    /**
     * @todo
     */
    public function resolveLocaleForKey(string $groupName, string $key, string $locale): ?string
    {
        throw new \LogicException('Not implemented');
    }

    public function getString(string $groupName, string $key, ?string $locale = null): ?string
    {
        if (null === $value = $this->getValue($groupName, $key, $locale)) {
            return null;
        }

        return Syntax::parseValueAsString($value);
    }

    public function setString(string $groupName, string $key, string $value, ?string $locale = null): void
    {
        $this->setValue($groupName, $key, Syntax::serializeString($value), $locale);
    }

    public function getStringList(string $groupName, string $key, ?string $locale = null): ?array
    {
        if ($value = $this->getValue($groupName, $key, $locale)) {
            return Syntax::parseValueAsString($value, $this->listSeparator);
        }

        return null;
    }

    public function setStringList(string $groupName, string $key, array $list, ?string $locale = null): void
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

    public function getValue(string $groupName, string $key, ?string $locale = null): ?string
    {
        if ($entry = $this->groups[$groupName]->entries[$key] ?? null) {
            if ($locale) {
                return $entry->getTranslation($this->locales[$locale] ??= Locale::of($locale));
            }
            return $entry->value;
        }
        return null;
    }

    public function setValue(string $groupName, string $key, string $value, ?string $locale = null): void
    {
        Syntax::validateGroupName($groupName);
        Syntax::validateKey($key);
        $this->setValueUnchecked($groupName, $key, $value, $locale);
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function setComment(string $comment): void
    {
        $this->comment = $comment;
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

    public function getKeyComment(string $groupName, string $key, string $comment, ?string $locale = null): ?string
    {
        if ($entry = $this->groups[$groupName]->entries[$key] ?? null) {
            if ($locale) {
                $entry = $entry->getTranslationEntry($this->locales[$locale] ??= Locale::of($locale));
            }
            return $entry?->comment;
        }

        return null;
    }

    public function setKeyComment(string $groupName, string $key, string $comment, ?string $locale = null): void
    {
        if ($entry = $this->groups[$groupName]->entries[$key] ?? null) {
            if ($locale && $tr = $entry->getTranslationEntry($this->locales[$locale] ??= Locale::of($locale))) {
                $tr->comment = $comment;
            } else {
                $entry->comment = $comment;
            }
        }
    }

    public function __toString(): string
    {
        $parts = [];
        if ($this->comment) {
            $parts[] = Syntax::serializeComment($this->comment);
        }
        $parts[] = implode("\n\n", $this->groups);

        return implode("\n\n", $parts) . "\n";
    }

    public function getIterator(): Traversable
    {
        foreach ($this->groups as $groupName => $group) {
            foreach ($group->entries as $key => $entry) {
                yield [$groupName, $key, $entry->value];
                foreach ($entry->getTranslations() as $tr) {
                    yield [$groupName, "{$key}[{$tr->locale}]", $tr->value];
                }
            }
        }
    }

    private function setValueUnchecked(string $groupName, string $key, string $value, ?string $locale): void
    {
        $group = $this->groups[$groupName] ??= new Group($groupName);
        $entry = $group->entries[$key] ??= new KeyValuePair($key, '');
        if ($locale) {
            $entry->setTranslation($locale, $value);
        } else {
            $entry->value = $value;
        }
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
            default => array_map(
                $cast,
                Syntax::parseValueAsString($rawValue, $this->listSeparator),
            ),
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
