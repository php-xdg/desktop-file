# xdg/desktop-file

PHP library for reading, editing and writing XDG desktop files.

Several freedesktop.org specifications use this file format,
like the [Desktop Entry Specification](https://specifications.freedesktop.org/desktop-entry-spec/desktop-entry-spec-latest.html)
and the [Icon Theme Specification](http://freedesktop.org/Standards/icon-theme-spec).

The syntax of key files is described in detail in the
[Desktop Entry Specification](https://specifications.freedesktop.org/desktop-entry-spec/desktop-entry-spec-latest.html#basic-format),
but here's a quick summary: desktop files consist of groups of key-value pairs, interspersed with comments.

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

## Installation

```sh
composer require xdg/desktop-file
```
