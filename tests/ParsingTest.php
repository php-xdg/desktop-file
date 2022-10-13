<?php declare(strict_types=1);

namespace Xdg\DesktopFile\Tests;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Xdg\DesktopFile\DesktopFile;
use Xdg\DesktopFile\Exception\ParseError;

final class ParsingTest extends TestCase
{
    /**
     * @dataProvider itParsesGroupHeadersProvider
     */
    public function testItParsesGroupHeaders(string $input, string $expected): void
    {
        Assert::assertSame($expected, trim((string)DesktopFile::parse($input)));
    }

    public function itParsesGroupHeadersProvider(): iterable
    {
        yield ['[Group]', '[Group]'];
        yield [
            "[Group1]\n[Group2]",
            "[Group1]\n\n[Group2]",
        ];
        yield 'group merging' => [
            "[G1]\n[G2]\n[G1]\n[G2]",
            "[G1]\n\n[G2]",
        ];
        yield 'preserve comments' => [
            "# Group 1\n[G1]\n# Group 2\n[G2]",
            "# Group 1\n[G1]\n\n# Group 2\n[G2]",
        ];
        yield 'preserve multiline comments' => [
            "# Group 1\n\n# Group 1.2\n[G1]",
            "# Group 1\n#\n# Group 1.2\n[G1]",
        ];
        yield 'group merging w/ comments' => [
            "# Group 1\n[G1]\n# Group 1.2\n[G1]",
            "# Group 1\n#\n# Group 1.2\n[G1]",
        ];
    }

    /**
     * @dataProvider itParsesKeyValuePairsProvider
     */
    public function testItParsesKeyValuePairs(string $input, string $expected): void
    {
        Assert::assertSame($expected, trim((string)DesktopFile::parse($input)));
    }

    public function itParsesKeyValuePairsProvider(): iterable
    {
        yield [
            "[G1]\nFoo=bar",
            "[G1]\nFoo=bar",
        ];
        yield 'whitespace around =' => [
            "[G1]\nFoo \t=\t bar",
            "[G1]\nFoo=bar",
        ];
        yield 'duplicate keys' => [
            "[G1]\nFoo=foo\nFoo=bar",
            "[G1]\nFoo=bar",
        ];
        yield 'group merging' => [
            "[G1]\nFoo=foo\n[G1]\nBar=bar",
            "[G1]\nFoo=foo\nBar=bar",
        ];
        yield 'group merging w/ duplicate keys' => [
            "[G1]\nFoo=foo\n[G1]\nFoo=bar",
            "[G1]\nFoo=bar",
        ];
        yield 'preserve comments' => [
            "[G1]\n# Foo\nFoo=foo\n# Bar\nBar=bar",
            "[G1]\n# Foo\nFoo=foo\n# Bar\nBar=bar",
        ];
        yield 'preserve multiline comments' => [
            "[G1]\n# Foo\n\n# Bar\nFoo=foo",
            "[G1]\n# Foo\n#\n# Bar\nFoo=foo",
        ];
        yield 'duplicate keys w/ comments' => [
            "[G1]\n# Foo\nFoo=foo\n# Bar\nFoo=bar",
            "[G1]\n# Bar\nFoo=bar",
        ];
    }

    public function testItIgnoresCommentWhenRequired(): void
    {
        $input = "#a\n[one]\n#b\nc=d";
        $expected = "[one]\nc=d";
        $kf = DesktopFile::parse($input, null, false);
        Assert::assertSame($expected, trim((string)$kf));
    }

    /**
     * @dataProvider itPreservesFileCommentsProvider
     */
    public function testItPreservesFileComments(string $input, string $expected): void
    {
        Assert::assertSame($expected, trim((string)DesktopFile::parse($input)));
    }

    public function itPreservesFileCommentsProvider(): iterable
    {
        yield 'comments only' => [
            "#a\n#b\n\n#c",
            "#a\n#b\n#\n#c",
        ];
        yield 'comments at end of file' => [
            "[a]\na=b\n#eof",
            "[a]\na=b\n\n#eof",
        ];
    }

    public function testItRemovesAllLocalesIfRequired(): void
    {
        $input = "[A]\na=b\na[fr]=c\na[en]=d";
        $expected = "[A]\na=b";
        $kf = DesktopFile::parse($input, false);
        Assert::assertSame($expected, trim((string)$kf));
    }

    public function testItKeepsMatchingLocalesIfRequired(): void
    {
        $input = <<<'INI'
        [A]
        a=b
        a[fr]=fr
        a[en]=nope
        a[fr_FR]=fr
        INI;
        $expected = "[A]\na=b\na[fr]=fr\na[fr_FR]=fr";
        $kf = DesktopFile::parse($input, 'fr_FR');
        Assert::assertSame($expected, trim((string)$kf));
    }

    /**
     * @dataProvider parseErrorsProvider
     */
    public function testParseErrors(string $input): void
    {
        $this->expectException(ParseError::class);
        DesktopFile::parse($input);
    }

    public function parseErrorsProvider(): iterable
    {
        yield 'missing group header' => [
            'foo=bar',
        ];
        yield 'invalid group header' => [
            '[Foo[bar]]',
        ];
        yield 'invalid entry, missing = sign' => [
            "[A]\nfoo",
        ];
        yield 'empty locale' => [
            "[A]\nfoo[]=qux",
        ];
        yield 'invalid locale' => [
            "[A]\nfoo[$$$]=qux",
        ];
        yield 'garbage after locale' => [
            "[A]\nfoo[af]zz=qux",
        ];
    }
}
