<?php declare(strict_types=1);

namespace Xdg\DesktopFile\Internal;

/**
 * @internal
 */
final class Locale implements \Stringable
{
    public readonly string $value;
    private readonly int $mask;
    private array $variants;

    // Masks for components of locale spec.
    // The ordering here is from least significant to most significant
    private const ENCODING = 0x01;
    private const TERRITORY = 0x02;
    private const MODIFIER = 0x04;

    private function __construct(
        public readonly string $language,
        public readonly ?string $territory,
        public readonly ?string $encoding,
        public readonly ?string $modifier,
    ) {
        $this->mask = 0
            | ($this->encoding ? self::ENCODING : 0)
            | ($this->territory ? self::TERRITORY : 0)
            | ($this->modifier ? self::MODIFIER : 0)
        ;
        $this->value = $this->language
            . ($this->territory ? "_{$this->territory}" : '')
            . ($this->encoding ? ".{$this->encoding}" : '')
            . ($this->modifier ? "@{$this->modifier}" : '')
        ;
    }

    public static function new(
        string $language,
        ?string $territory = null,
        ?string $encoding = null,
        ?string $modifier = null,
    ): self {
        return new self($language, $territory, $encoding, $modifier);
    }

    public static function of(self|string $locale): self
    {
        return match (true) {
            \is_string($locale) => self::parse($locale),
            default => $locale,
        };
    }

    /**
     * @return string[]
     */
    public function getVariants(): array
    {
        return $this->variants ??= $this->computeVariants();
    }

    public function matches(self|string $other): bool
    {
        $value = \is_string($other) ? $other : $other->value;
        if ($this->value === $value) {
            return true;
        }
        $lookup = array_flip($this->getVariants());
        return isset($lookup[$value]);
    }

    /**
     * @param string[] $candidates
     */
    public function select(array $candidates): ?string
    {
        if (array_is_list($candidates)) {
            $candidates = array_flip($candidates);
        }

        foreach ($this->getVariants() as $variant) {
            if (isset($candidates[$variant])) {
                return $variant;
            }
        }

        return null;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private const LOCALE_RX = <<<'REGEXP'
    /^
        (?<lang> [a-z]{2,3} )
        (?: _ (?<territory> [A-Z]{2} ) )?
        (?: \. (?<encoding> [^@]+ ) )?
        (?: @ (?<modifier> .+ ) )?
    $/x
    REGEXP;

    private static function parse(string $locale): self
    {
        if (preg_match(self::LOCALE_RX, $locale, $m, \PREG_UNMATCHED_AS_NULL)) {
            return new self($m['lang'], $m['territory'], $m['encoding'], $m['modifier']);
        }
        if (function_exists('locale_canonicalize') && $lid = locale_canonicalize($locale)) {
            $m = locale_parse($lid);
            if ($lang = $m['language'] ?? null) {
                $modifier = strtolower($m['script'] ?? $m['variant0'] ?? '');
                return new self(
                    $lang,
                    $m['region'] ?? null,
                    null,
                    $modifier ?: null,
                );
            }
        }

        throw new \InvalidArgumentException(sprintf(
            'Invalid locale: %s',
            $locale,
        ));
    }

    private function computeVariants(): array
    {
        $mask = $this->mask;
        $variants = [];
        for ($j = 0; $j <= $mask; $j++) {
            $i = $mask - $j;
            if (($i & ~$mask) === 0) {
                $variants[] = $this->language
                    . (($i & self::TERRITORY) ? "_{$this->territory}" : '')
                    . (($i & self::ENCODING) ? ".{$this->encoding}" : '')
                    . (($i & self::MODIFIER) ? "@{$this->modifier}" : '')
                ;
            }
        }

        return $variants;
    }
}
