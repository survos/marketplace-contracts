<?php

declare(strict_types=1);

namespace Survos\MarketplaceContracts\Tests;

use PHPUnit\Framework\TestCase;
use Survos\MarketplaceContracts\Model\Money;
use Survos\MarketplaceContracts\Model\ShippingSpec;

final class ShippingSpecTest extends TestCase
{
    public function testLinearCostAcrossUnits(): void
    {
        $spec = new ShippingSpec(
            serviceCode: 'eBayStandardEnvelope',
            firstItemCost: Money::fromDecimal('0.78', 'USD'),
            additionalItemCost: Money::fromDecimal('0.29', 'USD'),
        );

        self::assertSame('0.78', $spec->costFor(1)->toDecimal());
        self::assertSame('1.07', $spec->costFor(2)->toDecimal());
        self::assertSame('1.36', $spec->costFor(3)->toDecimal());
    }

    public function testAdditionalCostDefaultsToFirstWhenUnset(): void
    {
        $spec = new ShippingSpec('USPSGroundAdvantage', Money::fromDecimal('4.50', 'USD'));

        self::assertSame('9.00', $spec->costFor(2)->toDecimal());
    }

    public function testQuantityBelowOneCostsTheFirstItemRate(): void
    {
        $spec = new ShippingSpec('X', Money::fromDecimal('1.00', 'USD'));

        self::assertSame('1.00', $spec->costFor(0)->toDecimal());
    }

    public function testFreeShipping(): void
    {
        $spec = ShippingSpec::free('eBayStandardEnvelope', 'USD');

        self::assertTrue($spec->isFree());
        self::assertSame('0.00', $spec->costFor(5)->toDecimal());
    }
}
