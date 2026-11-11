<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Decimal money value object (integer minor units + bcmath string arithmetic).
 *
 * The database stores DECIMAL(12,2); PHP must not do binary-float arithmetic
 * on it. This class is the single place that converts between the three
 * representations used in the codebase:
 *  - DB string  "123.45"  (decimal:2 cast)
 *  - float      123.45    (legacy service signatures)
 *  - minor units 12345     (integer, exact)
 *
 * New code should use Money::fromDecimal()/Money::fromMinor() and Money::add()/
 * Money::sub() instead of float math. Existing float APIs delegate here so
 * rounding happens once, at the boundary, with bcmath.
 */
class Money
{
    /** @var int minor units (cents) */
    public readonly int $minor;

    private function __construct(int $minor)
    {
        $this->minor = $minor;
    }

    public static function fromMinor(int $minor): self
    {
        return new self($minor);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    /** From a DB decimal string ("123.45") or numeric. */
    public static function fromDecimal(string|int|float $v): self
    {
        // Normalise via string to avoid float representation error.
        $s = is_float($v) ? number_format($v, 2, '.', '') : (string) $v;
        $s = trim($s);
        if ($s === '' || $s === '-' || $s === '.') {
            return new self(0);
        }
        // bcmath: multiply by 100, round half-up to integer.
        $scaled = bcmul($s, '100', 2);
        // Round to nearest integer (half away from zero).
        $neg = str_starts_with($scaled, '-');
        $abs = ltrim($scaled, '-');
        [$int, $frac] = array_pad(explode('.', $abs, 2), 2, '0');
        $frac = str_pad(substr($frac, 0, 2), 2, '0');
        $minor = (int) $int * 100 + (int) $frac;
        // Handle .5+ rounding: bcmath with scale 2 already truncated; check 3rd digit.
        // Recompute with scale 3 for correct half-up.
        $scaled3 = bcmul($s, '1000', 3);
        $neg3 = str_starts_with($scaled3, '-');
        $abs3 = ltrim($scaled3, '-');
        [$int3, $frac3] = array_pad(explode('.', $abs3, 2), 2, '0');
        $frac3 = str_pad($frac3, 3, '0');
        $minor3 = (int) $int3 * 1000 + (int) substr($frac3, 0, 3);
        $minor = intdiv($minor3 + 5, 10);
        if ($neg3) {
            $minor = -$minor;
        }

        return new self($minor);
    }

    /** From a legacy float amount (rounded to 2dp). */
    public static function fromFloat(float $v): self
    {
        return self::fromDecimal(number_format($v, 2, '.', ''));
    }

    public function toDecimal(): string
    {
        $neg = $this->minor < 0;
        $abs = abs($this->minor);

        return ($neg ? '-' : '').intdiv($abs, 100).'.'.str_pad((string) ($abs % 100), 2, '0', STR_PAD_LEFT);
    }

    public function toFloat(): float
    {
        return $this->minor / 100;
    }

    public function add(self $o): self
    {
        return new self($this->minor + $o->minor);
    }

    public function sub(self $o): self
    {
        return new self($this->minor - $o->minor);
    }

    public function isZero(): bool
    {
        return $this->minor === 0;
    }

    public function isNegative(): bool
    {
        return $this->minor < 0;
    }

    public function abs(): self
    {
        return new self(abs($this->minor));
    }

    /** Sum a list of decimal strings / floats exactly. */
    public static function sum(array $values): self
    {
        $total = 0;
        foreach ($values as $v) {
            $total += self::fromDecimal($v)->minor;
        }

        return new self($total);
    }

    /** Round a float to 2dp and return as float (legacy helper). */
    public static function round(float $v): float
    {
        return self::fromFloat($v)->toFloat();
    }
}
