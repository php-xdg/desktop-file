<?php declare(strict_types=1);

namespace Xdg\DesktopFile\Tests\Internal;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Xdg\DesktopFile\Internal\Syntax;

final class SyntaxTest extends TestCase
{
    /**
     * @dataProvider splitLinesProvider
     */
    public function testSplitLines(string $input, array $expected): void
    {
        Assert::assertSame($expected, Syntax::splitLines($input));
    }

    public function splitLinesProvider(): iterable
    {
        yield 'CR' => [
            "foo\rbar\r",
            ['foo', 'bar', ''],
        ];
        yield 'LF' => [
            "foo\nbar\n",
            ['foo', 'bar', ''],
        ];
        yield 'CRLF' => [
            "foo\r\nbar\r\n",
            ['foo', 'bar', ''],
        ];
        yield 'unicode line-breaks' => [
            "Foo=A\u{0B}B\u{0C}",
            ["Foo=A\u{0B}B\u{0C}"],
        ];
    }
}
