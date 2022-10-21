# xdg/desktop-file

[![codecov](https://codecov.io/gh/php-xdg/desktop-file/branch/main/graph/badge.svg?token=DEJ4BIZKSZ)](https://codecov.io/gh/php-xdg/desktop-file)

PHP library for reading, editing and writing XDG desktop files.

## Installation

```sh
composer require xdg/desktop-file
```

## Desktop file format

The syntax of desktop files is described in detail in the
[Desktop Entry Specification](https://specifications.freedesktop.org/desktop-entry-spec/desktop-entry-spec-latest.html#basic-format),
but here's a quick summary: desktop files consist of groups of key-value pairs, interspersed with comments.

Several freedesktop.org specifications use this file format,
like the [Desktop Entry Specification](https://specifications.freedesktop.org/desktop-entry-spec/desktop-entry-spec-latest.html)
and the [Icon Theme Specification](http://freedesktop.org/Standards/icon-theme-spec).

```ini
# This is a comment
[First Group]
Name=Value
SomeString=Example\tthis value shows\nescaping

# localized strings are stored in multiple key-value pairs
Welcome=Hello
Welcome[de]=Hallo
Welcome[fr_FR]=Bonjour
Welcome[it]=Ciao
Welcome[be@latin]=Hello

[Another Group]
IsTheCakeALie=true
TheUniverseAndEverything=42
# lists are delimiter-separated strings:
ListOfNumbers=2;20;-200;0
ListOfBooleans=true;false;true;true
```

Blank lines are ignored.

Lines beginning with a `#` are considered comments.

Groups are started by a header line containing the group name enclosed in `[` and `]`,
and ended implicitly by the start of the next group or the end of the file.
Each key-value pair must be contained in a group.

Key-value pairs generally have the form `key=value`, except localized strings
which have the form `key[locale]=value`, with a locale identifier of the form `lang_COUNTRY@MODIFIER`
where COUNTRY and MODIFIER are optional.
Space characters before and after the `=` character are ignored.
Newline, tab, carriage return and backslash characters in value are escaped as `\n`, `\t`, `\r`, and `\\`, respectively.
To preserve leading spaces in values, these can also be escaped as `\s`.

Desktop files can store strings (possibly with localized variants),
integers, floats, booleans and lists of these.
Lists are separated by a separator character, typically `;` or `,`.
To use the list separator character in a value in a list, it has to be escaped by prefixing it with a backslash.

This syntax is obviously inspired by the `.ini` files commonly met on Windows, but there are some important differences:

  * `.ini` files use the `;` character to begin comments, desktop files use the `#` character.
  * Desktop files do not allow for ungrouped keys meaning only comments can precede the first group.
  * Desktop files are always encoded in UTF-8.
  * Key and group names are case-sensitive. For example, a group called `[GROUP]` is a different from `[group]`.
  * `.ini` files don't have a strongly typed boolean entry type, they only have `GetProfileInt()`.
    In desktop files, only `true` and `false` (in lower case) are allowed.

### Implementation note

This implementation differs from the Desktop Entry Specification in the following ways:
* groups in desktop files may contain the same key multiple times: the last entry wins.
* Desktop files may also contain multiple groups with the same name: they are merged together.
* Keys and group names in desktop files are not restricted to ASCII characters.
