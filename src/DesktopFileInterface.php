<?php declare(strict_types=1);

namespace Xdg\DesktopFile;

interface DesktopFileInterface extends \Stringable, \Traversable
{
    public function setListSeparator(string $separator): void;

    public function getListSeparator(): string;

    public function hasGroup(string $groupName): bool;

    public function getGroups(): array;

    public function getStartGroup(): ?string;

    public function removeGroup(string $groupName): void;

    public function hasKey(string $groupName, string $key): bool;

    public function getKeys(string $groupName): array;

    public function removeKey(string $groupName, string $key): void;

    public function getValue(string $groupName, string $key, ?string $locale = null): ?string;

    public function setValue(string $groupName, string $key, string $value, ?string $locale = null): void;

    public function getString(string $groupName, string $key, ?string $locale = null): ?string;

    public function setString(string $groupName, string $key, string $value, ?string $locale = null): void;

    /**
     * @return string[]|null
     */
    public function getStringList(string $groupName, string $key, ?string $locale = null): ?array;

    /**
     * @param string[] $list
     */
    public function setStringList(string $groupName, string $key, array $list, ?string $locale = null): void;

    public function getBoolean(string $groupName, string $key): ?bool;

    public function setBoolean(string $groupName, string $key, bool $value): void;

    /**
     * @return bool[]|null
     */
    public function getBooleanList(string $groupName, string $key): ?array;

    /**
     * @param bool[] $values
     */
    public function setBooleanList(string $groupName, string $key, array $values): void;

    public function getInteger(string $groupName, string $key): ?int;

    public function setInteger(string $groupName, string $key, int $value): void;

    /**
     * @return int[]|null
     */
    public function getIntegerList(string $groupName, string $key): ?array;

    /**
     * @param int[] $values
     */
    public function setIntegerList(string $groupName, string $key, array $values): void;

    public function getFloat(string $groupName, string $key): ?float;

    public function setFloat(string $groupName, string $key, float $value): void;

    /**
     * @return float[]|null
     */
    public function getFloatList(string $groupName, string $key): ?array;

    /**
     * @param float[] $values
     */
    public function setFloatList(string $groupName, string $key, array $values): void;

    public function getComment(): string;

    public function setComment(string $comment): void;

    public function getGroupComment(string $groupName): ?string;

    public function setGroupComment(string $groupName, string $comment): void;

    public function getKeyComment(string $groupName, string $key, string $comment, ?string $locale = null): ?string;

    public function setKeyComment(string $groupName, string $key, string $comment, ?string $locale = null): void;
}
