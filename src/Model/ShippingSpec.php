<?php

declare(strict_types=1);

namespace Survos\MarketplaceContracts\Model;

/**
 * Flat shipping for one listing: a cost for the first unit, and a cost for each
 * additional unit in the same order.
 *
 * Deliberately NOT banded. Neither eBay nor Mercado Libre can express a rule like
 * "1-3 units $0.90, 4-10 units $1.40" on a listing -- every automatic mechanism
 * they offer is linear (first + delta). On eBay the ways to get a band are:
 * separate listings per pack size, calculated shipping, or a manual combined
 * invoice. Modelling bands here would invent a feature the providers do not have
 * and quietly mis-price the result.
 *
 * So pack size belongs in the SKU, not in a shipping rule. See survos/mono#52.
 */
final readonly class ShippingSpec
{
    /**
     * @param string    $serviceCode        provider-specific shipping service, e.g. eBay's
     *                                      `eBayStandardEnvelope` or `USPSGroundAdvantage`.
     *                                      Opaque here; adapters validate it.
     * @param Money     $firstItemCost      charged once per order
     * @param Money|null $additionalItemCost charged per extra unit; null means the
     *                                       provider default (usually: same as first)
     * @param int       $handlingTimeDays   business days between sale and dispatch
     */
    public function __construct(
        public string $serviceCode,
        public Money $firstItemCost,
        public ?Money $additionalItemCost = null,
        public int $handlingTimeDays = 1,
    ) {
    }

    /**
     * Free shipping -- the cost is baked into the item price.
     *
     * Often the right answer when the real postage is small and predictable, since
     * buyers filter on it.
     */
    public static function free(string $serviceCode, string $currency, int $handlingTimeDays = 1): self
    {
        $zero = Money::ofMinorUnits(0, $currency);

        return new self($serviceCode, $zero, $zero, $handlingTimeDays);
    }

    public function isFree(): bool
    {
        return $this->firstItemCost->isZero();
    }

    /**
     * What a buyer taking $quantity units pays, assuming the provider applies the
     * linear rule. Useful for showing a seller the effect before publishing.
     */
    public function costFor(int $quantity): Money
    {
        if ($quantity <= 1) {
            return $this->firstItemCost;
        }

        $additional = $this->additionalItemCost ?? $this->firstItemCost;

        return $this->firstItemCost->plus($additional->multipliedBy($quantity - 1));
    }
}
