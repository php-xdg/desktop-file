<?php declare(strict_types=1);

namespace Xdg\DesktopFile\Tests;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Xdg\DesktopFile\DesktopFile;

final class LocalizationTest extends TestCase
{
    public function testFallbackToNonLocalizedValue(): void
    {
        $f = new DesktopFile();
        $f->setValue('Test', 'Foo', 'foo');
        $f->setValue('Test', 'Foo', 'bar', 'ru');
        Assert::assertSame('foo', $f->getValue('Test', 'Foo', 'fr'));
    }

    public function testGetExactKey(): void
    {
        $f = new DesktopFile();
        $f->setValue('Test', 'Foo', 'french', 'fr_FR');
        Assert::assertSame('french', $f->getValue('Test', 'Foo', 'fr_FR'));
    }

    public function testGetBestMatchingKey(): void
    {
        $f = new DesktopFile();
        $f->setValue('Test', 'Foo', 'fallback');
        $f->setValue('Test', 'Foo', 'generic', 'fr');
        $f->setValue('Test', 'Foo', 'france', 'fr_FR');
        //
        Assert::assertSame('france', $f->getValue('Test', 'Foo', 'fr_FR.UTF-8@anywhere'));
        Assert::assertSame('generic', $f->getValue('Test', 'Foo', 'fr_BE'));
    }

    public function testResolveLocale(): void
    {
        $f = new DesktopFile();
        $f->setValue('Test', 'Foo', 'fallback');
        $f->setValue('Test', 'Foo', 'generic', 'fr');
        $f->setValue('Test', 'Foo', 'france', 'fr_FR');
        //
        Assert::assertSame('fr_FR', $f->resolveLocaleForKey('Test', 'Foo', 'fr_FR.UTF-8@anywhere'));
        Assert::assertSame('fr', $f->resolveLocaleForKey('Test', 'Foo', 'fr_BE'));
    }
}
