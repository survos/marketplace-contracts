<?php

declare(strict_types=1);

namespace Survos\MarketplaceContracts\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Survos\MarketplaceContracts\Exception\MarketplaceConfigurationException;
use Survos\MarketplaceContracts\Model\Money;

final class MoneyTest extends TestCase
{
    #[DataProvider('decimals')]
    public function testRoundTripsDecimalStrings(string $input, int $minorUnits, string $output): void
    {
        $money = Money::fromDecimal($input, 'usd');

        self::assertSame($minorUnits, $money->minorUnits);
        self::assertSame('USD', $money->currency);
        self::assertSame($output, $money->toDecimal());
    }

    /** @return iterable<string, array{string, int, string}> */
    public static function decimals(): iterable
    {
        yield 'dave pack price' => ['4.00', 400, '4.00'];
        yield 'ebay standard envelope 1oz' => ['0.78', 78, '0.78'];
        yield 'no fraction' => ['4', 400, '4.00'];
        yield 'single decimal' => ['4.5', 450, '4.50'];
        yield 'sub-dollar' => ['0.07', 7, '0.07'];
        yield 'zero' => ['0', 0, '0.00'];
        yield 'negative' => ['-1.25', -125, '-1.25'];
    }

    public function testRejectsMorePrecisionThanTheCurrencyHas(): void
    {
        $this->expectException(MarketplaceConfigurationException::class);
        $this->expectExceptionMessage('has 3 decimal places but USD has 2');

        Money::fromDecimal('4.005', 'USD');
    }

    public function testRejectsNonDecimalInput(): void
    {
        $this->expectException(MarketplaceConfigurationException::class);

        Money::fromDecimal('$4.00', 'USD');
    }

    public function testRejectsUnknownCurrencyShape(): void
    {
        $this->expectException(MarketplaceConfigurationException::class);

        Money::fromDecimal('4.00', 'DOLLARS');
    }

    public function testZeroExponentCurrency(): void
    {
        $yen = Money::fromDecimal('1200', 'JPY', 0);

        self::assertSame(1200, $yen->minorUnits);
        self::assertSame('1200', $yen->toDecimal());
    }

    public function testRefusesToMixCurrencies(): void
    {
        $this->expectException(MarketplaceConfigurationException::class);
        $this->expectExceptionMessage('does not hold exchange rates');

        Money::fromDecimal('4.00', 'USD')->plus(Money::fromDecimal('4.00', 'MXN'));
    }

    public function testArithmeticStaysExact(): void
    {
        $tenCents = Money::fromDecimal('0.10', 'USD');

        $sum = $tenCents;
        for ($i = 0; $i < 9; ++$i) {
            $sum = $sum->plus($tenCents);
        }

        // The float version of this loop does not equal 1.00.
        self::assertSame('1.00', $sum->toDecimal());
    }
}
