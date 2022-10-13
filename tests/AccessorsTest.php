<?php declare(strict_types=1);

namespace Xdg\DesktopFile\Tests;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Xdg\DesktopFile\DesktopFile;
use Xdg\DesktopFile\Exception\SyntaxError;

final class AccessorsTest extends TestCase
{
    public function testHasGroup(): void
    {
        $f = new DesktopFile();
        Assert::assertSame([], $f->getGroups());
        Assert::assertNull($f->getStartGroup());
        Assert::assertFalse($f->hasGroup('G1'));
        //
        $f->setValue('G1', 'Test', 'foo1');
        $f->setValue('G2', 'Test', 'foo2');
        Assert::assertSame(['G1', 'G2'], $f->getGroups());
        Assert::assertSame('G1', $f->getStartGroup());
        Assert::assertTrue($f->hasGroup('G2'));
    }

    public function testRemoveGroup(): void
    {
        $f = new DesktopFile();
        $f->setValue('G1', 'Test', 'foo1');
        Assert::assertSame(['G1'], $f->getGroups());
        $f->removeGroup('G1');
        Assert::assertSame([], $f->getGroups());
        Assert::assertFalse($f->hasKey('G1', 'Test'));
    }

    public function testGetKeys(): void
    {
        $f = new DesktopFile();
        Assert::assertSame([], $f->getKeys('G1'));
        $f->setValue('G1', 'K1', 'v1');
        $f->setValue('G1', 'K2', 'v2');
        Assert::assertSame(['K1', 'K2'], $f->getKeys('G1'));
    }

    public function testRemoveKey(): void
    {
        $f = new DesktopFile();
        $f->setValue('G1', 'K1', 'v1');
        $f->setValue('G1', 'K2', 'v2');
        $f->removeKey('G1', 'K1');
        Assert::assertSame(['K2'], $f->getKeys('G1'));
    }

    public function testGettersReturnNullOnMissingKey(): void
    {
        $f = new DesktopFile();
        $group = 'Test';
        $key = '404';

        Assert::assertNull($f->getValue($group, $key));
        $f->setValue($group, 'Found', '200');

        Assert::assertNull($f->getValue($group, $key));
        Assert::assertNull($f->getString($group, $key));
        Assert::assertNull($f->getStringList($group, $key));
        Assert::assertNull($f->getBoolean($group, $key));
        Assert::assertNull($f->getBooleanList($group, $key));
        Assert::assertNull($f->getInteger($group, $key));
        Assert::assertNull($f->getIntegerList($group, $key));
        Assert::assertNull($f->getFloat($group, $key));
        Assert::assertNull($f->getFloatList($group, $key));
    }

    public function testListSeparator(): void
    {
        $f = new DesktopFile();
        Assert::assertSame(';', $f->getListSeparator());
        $f->setListSeparator('|');
        Assert::assertSame('|', $f->getListSeparator());
        $this->expectException(SyntaxError::class);
        $f->setListSeparator('\\');
    }

    /**
     * @dataProvider invalidNamesProvider
     */
    public function testInvalidNames(string $group, string $key): void
    {
        $f = new DesktopFile();
        $this->expectException(SyntaxError::class);
        $f->setValue($group, $key, '');
    }

    public function invalidNamesProvider(): iterable
    {
        yield 'group cannot contain []' => ['Gr[ou]p', 'Key'];
        yield 'group cannot contain newline' => ["Group\n1", 'Key'];
        yield 'key cannot contain =' => ['Group', 'K=ey'];
        yield 'key cannot contain []' => ['Group', 'K[ey]'];
        yield 'key cannot contain newline' => ['Group', "Ke\ny"];
    }

    /**
     * @dataProvider setStringProvider
     */
    public function testSetString(string $input, string $expected): void
    {
        $f = new DesktopFile();
        $f->setString('Test', 'String', $input);
        Assert::assertSame($expected, $f->getValue('Test', 'String'));
    }

    public function setStringProvider(): iterable
    {
        yield 'simple string' => [
            'foobar',
            'foobar',
        ];
        yield 'escape sequences' => [
            "a\nb\tc\rd\\e",
            'a\\nb\\tc\\rd\\\\e',
        ];
        yield 'escapes backslashes' => [
            '\\a\\b\\\\c',
            '\\\\a\\\\b\\\\\\\\c',
        ];
        yield 'leading whitespace' => [
            " \tfoo bar",
            '\\s\\tfoo bar',
        ];
    }

    /**
     * @dataProvider getStringProvider
     */
    public function testGetString(string $input, string $expected): void
    {
        $f = new DesktopFile();
        $f->setValue('Test', 'String', $input);
        Assert::assertSame($expected, $f->getString('Test', 'String'));
    }

    public function getStringProvider(): iterable
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
     * @dataProvider setStringListProvider
     */
    public function testSetStringList(array $input, string $sep, string $expected): void
    {
        $f = new DesktopFile();
        $f->setListSeparator($sep);
        $f->setStringList('Test', 'Strings', $input);
        Assert::assertSame($expected, $f->getValue('Test', 'Strings'));
    }

    public function setStringListProvider(): iterable
    {
        yield 'simple list (;)' => [
            [1, 2, 3], ';',
            '1;2;3',
        ];
        yield 'simple list (,)' => [
            [1, 2, 3], ',',
            '1,2,3',
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

    /**
     * @dataProvider getStringListProvider
     */
    public function testGetStringList(string $input, array $expected): void
    {
        $f = new DesktopFile();
        $f->setValue('Test', 'Strings', $input);
        Assert::assertSame($expected, $f->getStringList('Test', 'Strings'));
    }

    public function getStringListProvider(): iterable
    {
        yield 'simple' => [
            'a;b;c;',
            ['a', 'b', 'c'],
        ];
        yield 'simple, no trailing delimiter' => [
            'a;b;c',
            ['a', 'b', 'c'],
        ];
        yield 'empty items' => [
            'a;;b;c;;',
            ['a', '', 'b', 'c', ''],
        ];
        yield 'escaped delimiters' => [
            'a;b\\;c;d',
            ['a', 'b\\;c', 'd'],
        ];
        yield 'escaped delimiters #2' => [
            // a;b\\;c;d
            'a;b\\\\;c;d',
            ['a', 'b\\', 'c', 'd'],
        ];
    }

    public function testSetBoolean(): void
    {
        $f = new DesktopFile();
        $f->setBoolean('Test', 'True', true);
        $f->setBoolean('Test', 'False', false);
        Assert::assertSame('true', $f->getValue('Test', 'True'));
        Assert::assertSame('false', $f->getValue('Test', 'False'));
    }

    /**
     * @dataProvider getBooleanProvider
     */
    public function testGetBoolean(string $input, bool $expected, ?string $exception = null): void
    {
        if ($exception) {
            $this->expectException($exception);
        }
        $f = new DesktopFile();
        $f->setValue('Test', 'Bool', $input);
        Assert::assertSame($expected, $f->getBoolean('Test', 'Bool'));
    }

    public function getBooleanProvider(): iterable
    {
        yield ['true', true];
        yield ['false', false];
        yield ['1', true, SyntaxError::class];
        yield ['0', false, SyntaxError::class];
    }

    public function testSetBooleanList(): void
    {
        $f = new DesktopFile();
        $f->setBooleanList('Test', 'Booleans', [true, false, true]);
        Assert::assertSame('true;false;true', $f->getValue('Test', 'Booleans'));
    }

    /**
     * @dataProvider getBooleanListProvider
     */
    public function testGetBooleanList(string $input, array $expected, ?string $exception = null): void
    {
        if ($exception) {
            $this->expectException($exception);
        }
        $f = new DesktopFile();
        $f->setValue('Test', 'Bool', $input);
        Assert::assertSame($expected, $f->getBooleanList('Test', 'Bool'));
    }

    public function getBooleanListProvider(): iterable
    {
        yield ['true;false', [true, false]];
        yield ['true;false;true;', [true, false, true]];
        yield ['false;0;true', [], SyntaxError::class];
    }

    public function testSetInteger(): void
    {
        $f = new DesktopFile();
        $f->setInteger('Test', 'Int', 42);
        Assert::assertSame('42', $f->getValue('Test', 'Int'));
    }

    /**
     * @dataProvider getIntegerProvider
     */
    public function testGetInteger(string $input, int $expected, ?string $exception = null): void
    {
        if ($exception) {
            $this->expectException($exception);
        }
        $f = new DesktopFile();
        $f->setValue('Test', 'Int', $input);
        Assert::assertSame($expected, $f->getInteger('Test', 'Int'));
    }

    public function getIntegerProvider(): iterable
    {
        yield ['42', 42];
        yield ['3.14', 3]; // TODO: throw exception for loss of precision?
        yield ['zaroo', 0, SyntaxError::class];
    }

    public function testSetIntegerList(): void
    {
        $f = new DesktopFile();
        $f->setIntegerList('Test', 'Integers', [1, 2, 3]);
        Assert::assertSame('1;2;3', $f->getValue('Test', 'Integers'));
    }

    /**
     * @dataProvider getIntegerListProvider
     */
    public function testGetIntegerList(string $input, array $expected, ?string $exception = null): void
    {
        if ($exception) {
            $this->expectException($exception);
        }
        $f = new DesktopFile();
        $f->setValue('Test', 'IntList', $input);
        Assert::assertSame($expected, $f->getIntegerList('Test', 'IntList'));
    }

    public function getIntegerListProvider(): iterable
    {
        yield ['0;1;2', [0, 1, 2]];
        yield ['1;2;3;', [1, 2, 3]];
        yield ['42;3.14;6.66', [42, 3, 6]];
        yield ['1;false;null', [], SyntaxError::class];
    }

    public function testSetFloat(): void
    {
        $f = new DesktopFile();
        $f->setFloat('Test', 'Int', 42);
        $f->setFloat('Test', 'Float', 66.6);
        Assert::assertSame('42', $f->getValue('Test', 'Int'));
        Assert::assertSame('66.6', $f->getValue('Test', 'Float'));
    }

    /**
     * @dataProvider getFloatProvider
     */
    public function testGetFloat(string $input, float $expected, ?string $exception = null): void
    {
        if ($exception) {
            $this->expectException($exception);
        }
        $f = new DesktopFile();
        $f->setValue('Test', 'Float', $input);
        Assert::assertSame($expected, $f->getFloat('Test', 'Float'));
    }

    public function getFloatProvider(): iterable
    {
        yield ['3.14', 3.14];
        yield ['42', 42.0];
        yield ['zaroo', 0, SyntaxError::class];
    }

    public function testSetFloatList(): void
    {
        $f = new DesktopFile();
        $f->setFloatList('Test', 'Floats', [3.14, 42, 66.6]);
        Assert::assertSame('3.14;42;66.6', $f->getValue('Test', 'Floats'));
    }

    /**
     * @dataProvider getFloatListProvider
     */
    public function testGetFloatList(string $input, array $expected, ?string $exception = null): void
    {
        if ($exception) {
            $this->expectException($exception);
        }
        $f = new DesktopFile();
        $f->setValue('Test', 'FloatList', $input);
        Assert::assertSame($expected, $f->getFloatList('Test', 'FloatList'));
    }

    public function getFloatListProvider(): iterable
    {
        yield ['0;1;2', [0.0, 1.0, 2.0]];
        yield ['1;2;3;', [1.0, 2.0, 3.0]];
        yield ['42;3.14;6.66', [42.0, 3.14, 6.66]];
        yield ['1;false;null', [], SyntaxError::class];
    }
}
