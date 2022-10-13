<?php declare(strict_types=1);

namespace Xdg\DesktopFile\Tests;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Xdg\DesktopFile\DesktopFile;

final class IterationTest extends TestCase
{
    public function testGetIterator(): void
    {
        $f = new DesktopFile();
        $f->setValue('1', 'a', '1.a');
        $f->setValue('1', 'a', '1.a.b', 'll');
        $f->setValue('2', 'a', '2.a');
        $f->setValue('2', 'a', '2.a.b', 'll');
        $expected = [
            ['1', 'a', '1.a'],
            ['1', 'a[ll]', '1.a.b'],
            ['2', 'a', '2.a'],
            ['2', 'a[ll]', '2.a.b'],
        ];
        Assert::assertSame($expected, iterator_to_array($f));
    }
}
