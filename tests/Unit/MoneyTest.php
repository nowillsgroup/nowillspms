<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function test_decimal_addition_has_no_binary_float_drift(): void
    {
        self::assertSame('0.30', Money::fromDecimal('0.10')->add(Money::fromDecimal('0.20'))->toDecimal());
    }

    #[DataProvider('roundingCases')]
    public function test_rounds_to_minor_units(string $input, string $expected): void
    {
        self::assertSame($expected, Money::fromDecimal($input)->toDecimal());
    }

    public static function roundingCases(): array
    {
        return [['1.004', '1.00'], ['1.005', '1.01'], ['-1.005', '-1.01'], ['999999.999', '1000000.00']];
    }
}
