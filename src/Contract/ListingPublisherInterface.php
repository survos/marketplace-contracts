<?php

declare(strict_types=1);

namespace Survos\MarketplaceContracts\Contract;

use Survos\MarketplaceContracts\Exception\ListingRejectedException;
use Survos\MarketplaceContracts\Model\ListingDraft;
use Survos\MarketplaceContracts\Model\MarketplaceLimits;
use Survos\MarketplaceContracts\Model\PublishedListing;

interface ListingPublisherInterface
{
    /**
     * Provider caps a caller should respect while generating content.
     *
     * Read these BEFORE generating a title, not after. eBay's 80-character cap is
     * the usual casualty.
     */
    public function limits(): MarketplaceLimits;

    /**
     * Publish the draft.
     *
     * What this costs varies enormously and deliberately is not exposed: on eBay it
     * is an inventory item, an offer, and a publish (against previously created
     * business policies and a merchant location); on Mercado Libre it is one POST.
     *
     * @throws ListingRejectedException when the provider refuses the listing
     */
    public function publish(ListingDraft $draft): PublishedListing;

    /**
     * Take a published listing down.
     *
     * @throws \Survos\MarketplaceContracts\Exception\UnsupportedMarketplaceOperation
     *         when the adapter lacks {@see \Survos\MarketplaceContracts\Model\ProviderCapability::Withdraw}
     */
    public function withdraw(string $externalId): void;
}
