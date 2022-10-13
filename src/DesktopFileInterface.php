<?php declare(strict_types=1);

namespace Xdg\DesktopFile;

use Xdg\Locale\Locale;

interface DesktopFileInterface extends \Stringable, \Traversable
{
    /**
     * Sets the character used to separate values in lists.
     *
     * Typically, ';' or ',' are used as separators.
     * The default list separator is ';'.
     */
    public function setListSeparator(string $separator): void;

    /**
     * Returns the character used to separate values in lists.
     */
    public function getListSeparator(): string;

    /**
     * Returns whether the file contains a group with the given name.
     */
    public function hasGroup(string $groupName): bool;

    /**
     * Returns all group names in the file.
     *
     * @return string[]
     */
    public function getGroups(): array;

    /**
     * Returns the name of the start group of the file.
     */
    public function getStartGroup(): ?string;

    /**
     * Removes the given group.
     */
    public function removeGroup(string $groupName): void;

    /**
     * Returns whether the given group contains the given key.
     */
    public function hasKey(string $groupName, string $key): bool;

    /**
     * Returns all keys in a group.
     *
     * @return string[]
     */
    public function getKeys(string $groupName): array;

    /**
     * Removes a key from a group.
     */
    public function removeKey(string $groupName, string $key): void;

    /**
     * Returns the actual locale used when looking up a localized key.
     */
    public function resolveLocaleForKey(string $groupName, string $key, Locale|string $locale): ?string;

    /**
     * Returns the raw value associated with `$key` under `$groupName`,
     * optionally localized using `$locale`.
     *
     * To retrieve a string with unescaped characters, use {@see self::getString()}
     */
    public function getValue(string $groupName, string $key, Locale|string|null $locale = null): ?string;

    /**
     * Sets the raw value associated with `$key` under `$groupName`,
     * optionally localized using `$locale`.
     *
     * To set a string value which may contain characters that need escaping (such as newlines or spaces),
     * use {@see self::setString()}.
     */
    public function setValue(string $groupName, string $key, string $value, Locale|string|null $locale = null): void;

    /**
     * Returns the value associated with `$key` under `$groupName`, optionally localized using `$locale`,
     * or `null` if the key was not found.
     *
     * Unlike {@see self::getValue()}, this method handles escape sequences like "\n".
     */
    public function getString(string $groupName, string $key, Locale|string|null $locale = null): ?string;

    /**
     * Sets the value associated with `$key` under `$groupName`, optionally localized using `$locale`.
     *
     * Unlike {@see self::setValue()}, this method handles characters that need escaping, such as newlines.
     */
    public function setString(string $groupName, string $key, string $value, Locale|string|null $locale = null): void;

    /**
     * Returns the values associated with `$key` under `$groupName`, optionally localized using `$locale`,
     * or `null` if the key was not found.
     *
     * @return string[]|null
     */
    public function getStringList(string $groupName, string $key, Locale|string|null $locale = null): ?array;

    /**
     * Sets the values associated with `$key` under `$groupName`, optionally localized using `$locale`.
     *
     * @param string[] $list
     */
    public function setStringList(string $groupName, string $key, array $list, Locale|string|null $locale = null): void;

    /**
     * Returns the value associated with `$key` under `$groupName` as a boolean,
     * or `null` if the key was not found.
     */
    public function getBoolean(string $groupName, string $key): ?bool;

    public function setBoolean(string $groupName, string $key, bool $value): void;

    /**
     * Returns the value associated with `$key` under `$groupName` as an array of booleans,
     * or `null` if the key was not found.
     *
     * @return bool[]|null
     */
    public function getBooleanList(string $groupName, string $key): ?array;

    /**
     * @param bool[] $values
     */
    public function setBooleanList(string $groupName, string $key, array $values): void;

    /**
     * Returns the value associated with `$key` under `$groupName` as an integer,
     * or `null` if the key was not found.
     */
    public function getInteger(string $groupName, string $key): ?int;

    public function setInteger(string $groupName, string $key, int $value): void;

    /**
     * Returns the value associated with `$key` under `$groupName` as an array of integers,
     * or `null` if the key was not found.
     *
     * @return int[]|null
     */
    public function getIntegerList(string $groupName, string $key): ?array;

    /**
     * @param int[] $values
     */
    public function setIntegerList(string $groupName, string $key, array $values): void;

    /**
     * Returns the value associated with `$key` under `$groupName` as a float,
     * or `null` if the key was not found.
     */
    public function getFloat(string $groupName, string $key): ?float;

    public function setFloat(string $groupName, string $key, float $value): void;

    /**
     * Returns the value associated with `$key` under `$groupName` as an array of floats,
     * or `null` if the key was not found.
     *
     * @return float[]|null
     */
    public function getFloatList(string $groupName, string $key): ?array;

    /**
     * @param float[] $values
     */
    public function setFloatList(string $groupName, string $key, array $values): void;

    /**
     * Returns the comment associated with the start group.
     */
    public function getStartComment(): ?string;

    /**
     * Sets the comment associated with the start group.
     */
    public function setStartComment(string $comment): void;

    /**
     * Returns the comment at the end of file.
     */
    public function getEndComment(): string;

    /**
     * Sets the comment at the end of file.
     */
    public function setEndComment(string $comment): void;

    /**
     * Returns the comment associated with the given group.
     */
    public function getGroupComment(string $groupName): ?string;

    /**
     * Sets the comment associated with the given group.
     * The comment will be written above the group header in the file.
     */
    public function setGroupComment(string $groupName, string $comment): void;

    /**
     * Returns the comment associated with the given key.
     */
    public function getKeyComment(string $groupName, string $key, Locale|string|null $locale = null): ?string;

    /**
     * Sets the comment associated with the given key.
     * The comment will be written above the key in the file.
     */
    public function setKeyComment(string $groupName, string $key, string $comment, Locale|string|null $locale = null): void;
}
