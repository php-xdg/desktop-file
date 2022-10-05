<?php declare(strict_types=1);

namespace Xdg\DesktopEntry\Tests\KeyFile\Internal;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Xdg\DesktopEntry\KeyFile\Internal\Syntax;

final class SyntaxTest extends TestCase
{
    /**
     * @dataProvider parseValueAsStringProvider
     */
    public function testParseValueAsString(string $input, string $expected): void
    {
        Assert::assertSame($expected, Syntax::parseValueAsString($input));
    }

    public function parseValueAsStringProvider(): iterable
    {
        yield '\s => " "' => ['\s', ' '];
        yield '\t => "\t"' => ['\t', "\t"];
        yield '\n => "\n"' => ['\n', "\n"];
        yield '\r => "\r"' => ['\r', "\r"];
        yield '\\ => "\\"' => ['\\', "\\"];
        yield '\\\\ => "\\"' => ['\\\\', "\\"];
        //
        yield '\a' => ['\a', '\a'];
    }

    /**
     * @dataProvider parseValueAsStringListProvider
     */
    public function testParseValueAsStringList(string $input, string $sep, array $expected): void
    {
        Assert::assertSame($expected, Syntax::parseValueAsString($input, $sep));
    }

    public function parseValueAsStringListProvider(): iterable
    {
        yield 'simple' => [
            'a;b;c;', ';',
            ['a', 'b', 'c'],
        ];
        yield 'simple, no trailing delimiter' => [
            'a;b;c', ';',
            ['a', 'b', 'c'],
        ];
        yield 'empty items' => [
            'a;;b;c;;', ';',
            ['a', '', 'b', 'c', ''],
        ];
        yield 'escaped delimiters' => [
            'a;b\\;c;d', ';',
            ['a', 'b\\;c', 'd'],
        ];
        yield 'escaped delimiters #2' => [
            // a;b\\;c;d
            'a;b\\\\;c;d', ';',
            ['a', 'b\\', 'c', 'd'],
        ];
    }

    /**
     * @dataProvider serializeStringProvider
     */
    public function testSerializeString(string $input, string $expected): void
    {
        Assert::assertSame($expected, Syntax::serializeString($input));
    }

    public function serializeStringProvider(): iterable
    {
        yield 'escapes leading whitespace' => [
            "\t foo bar",
            '\t\sfoo bar',
        ];
        yield 'escapes new lines' => [
            "foo\nbar\r\n",
            'foo\nbar\r\n',
        ];
        yield 'escapes backslashes' => [
            '\\a\\b\\\\c',
            '\\\\a\\\\b\\\\\\\\c',
        ];
    }

    /**
     * @dataProvider serializeStringListProvider
     */
    public function testSerializeStringList(array $input, string $sep, string $expected): void
    {
        Assert::assertSame($expected, Syntax::serializeStringList($input, $sep));
    }

    public function serializeStringListProvider(): iterable
    {
        yield 'simple list' => [
            [1, 2, 3], ';',
            '1;2;3',
        ];
        yield 'escapes delimiter' => [
            ['a', 'b;c', 'd\\;e'], ';',
            'a;b\;c;d\\\\\\;e',
        ];
        yield 'handles escape sequences' => [
            [' a', "\n;\r", "b\tc"], ';',
            '\sa;\n\;\r;b\tc',
        ];
    }
}
