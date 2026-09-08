<?php

declare(strict_types=1);

namespace Survos\MarketplaceContracts\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Survos\MarketplaceContracts\Exception\MarketplaceConfigurationException;
use Survos\MarketplaceContracts\Model\Money;
use Survos\MarketplaceContracts\Model\ValueEstimate;
use Survos\MarketplaceContracts\Model\ValueRange;
use Survos\MarketplaceContracts\Model\ValueSituation;

#[CoversClass(ValueRange::class)]
#[CoversClass(ValueEstimate::class)]
final class ValueRangeTest extends TestCase
{
    public function testBoundsMustShareACurrency(): void
    {
        $this->expectException(MarketplaceConfigurationException::class);
        $this->expectExceptionMessageMatches('/one currency/');

        ValueRange::between(Money::fromDecimal('8.00', 'USD'), Money::fromDecimal('15.00', 'MXN'));
    }

    public function testInvertedBoundsThrowRatherThanSwapping(): void
    {
        // Swapping silently would hide the upstream bug that produced the pair.
        $this->expectException(MarketplaceConfigurationException::class);
        $this->expectExceptionMessageMatches('/inverted/');

        ValueRange::fromDecimals('15.00', '8.00', 'USD');
    }

    public function testAPointEstimateIsJustAnEqualRange(): void
    {
        $point = ValueRange::exactly(Money::fromDecimal('9.99', 'USD'));

        self::assertTrue($point->isPoint());
        self::assertSame('9.99', $point->low->toDecimal());
        self::assertTrue($point->equals(ValueRange::fromDecimals('9.99', '9.99', 'USD')));
    }

    public function testMidpointRoundsDownToAWholeMinorUnit(): void
    {
        // 800 + 1500 = 2300, halved is 1150 exactly.
        self::assertSame('11.50', ValueRange::fromDecimals('8.00', '15.00', 'USD')->midpoint()->toDecimal());

        // An odd total must not produce a fractional cent.
        self::assertSame('11.49', ValueRange::fromDecimals('8.00', '14.99', 'USD')->midpoint()->toDecimal());
    }

    public function testConfidenceIsBounded(): void
    {
        $this->expectException(MarketplaceConfigurationException::class);

        ValueEstimate::of(
            ValueSituation::Resale,
            ValueRange::fromDecimals('8.00', '15.00', 'USD'),
            1.4,
        );
    }

    public function testBlankBasisBecomesNullRatherThanAnEmptyString(): void
    {
        $estimate = ValueEstimate::of(
            ValueSituation::GarageSale,
            ValueRange::fromDecimals('1.00', '3.00', 'USD'),
            0.8,
            '   ',
        );

        self::assertNull($estimate->basis);
        self::assertTrue($estimate->isConfidentAtLeast(0.8));
        self::assertFalse($estimate->isConfidentAtLeast(0.81));
    }
}
