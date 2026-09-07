<?php

declare(strict_types=1);

namespace Survos\MarketplaceContracts\Contract;

use Survos\MarketplaceContracts\Model\ProviderCapability;

/**
 * One marketplace account, ready to use.
 *
 * Deliberately narrow. Only the surfaces that genuinely generalize across eBay and
 * Mercado Libre live here; everything provider-shaped (eBay's business policies and
 * merchant locations, Mercado Libre's test users) belongs on the concrete adapter,
 * where it can be typed honestly instead of being flattened into a lowest common
 * denominator that describes neither provider.
 */
interface MarketplaceAdapterInterface extends CategoryResolverInterface, ListingPublisherInterface
{
    /** Stable driver id, e.g. `ebay`, `mercadolibre`. */
    public function provider(): string;

    /** Provider site this adapter is bound to, e.g. `EBAY_US`, `MLM`. */
    public function site(): string;

    /** @return list<ProviderCapability> */
    public function capabilities(): array;

    public function supports(ProviderCapability $capability): bool;
}
