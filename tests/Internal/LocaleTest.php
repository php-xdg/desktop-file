<?php declare(strict_types=1);

namespace Xdg\DesktopFile\Tests\Internal;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Xdg\DesktopFile\Internal\Locale;

final class LocaleTest extends TestCase
{
    public function testFromLocale(): void
    {
        $locale = Locale::new('ll');
        Assert::assertSame($locale, Locale::of($locale));
    }

    /**
     * @dataProvider fromStringProvider
     */
    public function testFromString(string $input, Locale $expected): void
    {
        Assert::assertEquals($expected, Locale::of($input));
    }

    public function fromStringProvider(): iterable
    {
        $tests = [
            // POSIX
            'll' => ['ll', null, null, null],
            'll@bar' => ['ll', null, null, 'bar'],
            'll_CC' => ['ll', 'CC', null, null],
            'll.foo' => ['ll', null, 'foo', null],
            'll.foo@bar' => ['ll', null, 'foo', 'bar'],
            'll_CC@bar' => ['ll', 'CC', null, 'bar'],
            'll_CC.foo' => ['ll', 'CC', 'foo', null],
            'll_CC.foo@bar' => ['ll', 'CC', 'foo', 'bar'],
            // others
            'll-CC' => ['ll', 'CC'],
            'll-CC@foo' => ['ll', 'CC', null, 'foo'],
            'fr-Latn-BE' => ['fr', 'BE', null, 'latn'],
            'fr-fr-latin' => ['fr', 'FR', null, 'latin'],
        ];
        foreach ($tests as $input => $args) {
            yield $input => [$input, Locale::new(...$args)];
        }
    }

    /**
     * @dataProvider toStringProvider
     */
    public function testToString(Locale $locale, string $expected): void
    {
        Assert::assertSame($expected, (string)$locale);
    }

    public function toStringProvider(): iterable
    {
        $tests = [
            // POSIX
            'll' => ['ll', null, null, null],
            'll@bar' => ['ll', null, null, 'bar'],
            'll_CC' => ['ll', 'CC', null, null],
            'll.foo' => ['ll', null, 'foo', null],
            'll.foo@bar' => ['ll', null, 'foo', 'bar'],
            'll_CC@bar' => ['ll', 'CC', null, 'bar'],
            'll_CC.foo' => ['ll', 'CC', 'foo', null],
            'll_CC.foo@bar' => ['ll', 'CC', 'foo', 'bar'],
        ];
        foreach ($tests as $expected => $args) {
            yield $expected => [Locale::new(...$args), $expected];
        }
    }

    /**
     * @dataProvider getVariantsProvider
     */
    public function testGetVariants(Locale $locale, array $expected): void
    {
        Assert::assertSame($expected, $locale->getVariants());
    }

    public function getVariantsProvider(): iterable
    {
        yield 'll' => [
            Locale::new('ll'),
            ['ll'],
        ];
        yield 'll@bar' => [
            Locale::new('ll', null, null, 'bar'),
            ['ll@bar', 'll'],
        ];
        yield 'll_CC' => [
            Locale::new('ll', 'CC'),
            ['ll_CC', 'll'],
        ];
        yield 'll.foo' => [
            Locale::new('ll', null, 'foo'),
            ['ll.foo', 'll'],
        ];
        yield 'll.foo@bar' => [
            Locale::new('ll', null, 'foo', 'bar'),
            ['ll.foo@bar', 'll@bar', 'll.foo', 'll'],
        ];
        yield 'll_CC@bar' => [
            Locale::new('ll', 'CC', null, 'bar'),
            ['ll_CC@bar', 'll@bar', 'll_CC', 'll'],
        ];
        yield 'll_CC.foo' => [
            Locale::new('ll', 'CC', 'foo'),
            ['ll_CC.foo', 'll_CC', 'll.foo', 'll'],
        ];
        yield 'll_CC.foo@bar' => [
            Locale::new('ll', 'CC', 'foo', 'bar'),
            ['ll_CC.foo@bar', 'll_CC@bar', 'll.foo@bar', 'll@bar', 'll_CC.foo', 'll_CC', 'll.foo', 'll'],
        ];
    }

    /**
     * @dataProvider matchesProvider
     */
    public function testMatches(Locale $locale, Locale|string $other, bool $expected): void
    {
        Assert::assertSame($expected, $locale->matches($other));
    }

    public function matchesProvider(): iterable
    {
        $variants = ['ll_CC.foo@bar', 'll_CC@bar', 'll.foo@bar', 'll@bar', 'll_CC.foo', 'll_CC', 'll.foo', 'll'];
        $createCase = static function(Locale $locale, array $matches) use ($variants) {
            $expected = array_combine(
                $variants,
                array_map(fn($v) => \in_array($v, $matches), $variants),
            );
            foreach ($expected as $variant => $e) {
                $key = sprintf(
                    '%s %s %s',
                    $locale,
                    $e ? 'matches' : 'does not match',
                    $variant,
                );
                yield $key => [$locale, $variant, $e];
                yield "{$key} (object)" => [$locale, Locale::of($variant), $e];
            }
        };

        yield from $createCase(
            Locale::new('ll'),
            ['ll'],
        );
        yield from $createCase(
            Locale::new('ll', null, null, 'bar'),
            ['ll@bar', 'll'],
        );
        yield from $createCase(
            Locale::new('ll', 'CC'),
            ['ll_CC', 'll'],
        );
        yield from $createCase(
            Locale::new('ll', null, 'foo'),
            ['ll.foo', 'll'],
        );
        yield from $createCase(
            Locale::new('ll', null, 'foo', 'bar'),
            ['ll.foo@bar', 'll@bar', 'll.foo', 'll'],
        );
        yield from $createCase(
            Locale::new('ll', 'CC', null, 'bar'),
            ['ll_CC@bar', 'll@bar', 'll_CC', 'll'],
        );
        yield from $createCase(
            Locale::new('ll', 'CC', 'foo'),
            ['ll_CC.foo', 'll_CC', 'll.foo', 'll'],
        );
        yield from $createCase(
            Locale::new('ll', 'CC', 'foo', 'bar'),
            ['ll_CC.foo@bar', 'll_CC@bar', 'll.foo@bar', 'll@bar', 'll_CC.foo', 'll_CC', 'll.foo', 'll'],
        );
        //
        yield 'll does not match fr' => [
            Locale::new('ll'), 'fr', false,
        ];
    }

    /**
     * @dataProvider selectProvider
     */
    public function testSelect(Locale $locale, array $candidates, ?string $expected): void
    {
        Assert::assertSame($expected, $locale->select($candidates));
    }

    public function selectProvider(): iterable
    {
        yield 'll selects nothing in ["fr"]' => [
            Locale::new('ll'), ['fr'], null,
        ];
        yield 'll selects ll in ["fr", "ll"]' => [
            Locale::new('ll'), ['fr', 'll'], 'll',
        ];
    }
}
