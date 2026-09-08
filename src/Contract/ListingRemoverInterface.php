<?php

declare(strict_types=1);

namespace Survos\MarketplaceContracts\Contract;

/**
 * Permanent deletion, as distinct from withdraw().
 *
 * withdraw() means "stop selling this" and is reversible or at least recoverable
 * everywhere it exists — eBay ends the offer but keeps it, Mercado Libre closes
 * the item but the record survives, Etsy deactivates. delete() destroys the
 * listing outright, along with its views, favourites and stats.
 *
 * Separated because the two are not interchangeable and the wrong one is expensive
 * in opposite directions: withdrawing when you meant to delete leaves clutter,
 * deleting when you meant to withdraw throws away a listing's history and the fee
 * that was paid for it.
 *
 * A seller who has sold an item ELSEWHERE almost always wants withdraw(). delete()
 * is for a listing that should never have existed.
 */
interface ListingRemoverInterface
{
    /**
     * Destroy the listing permanently. Not reversible.
     *
     * @param string $externalId the handle publish() returned
     */
    public function delete(string $externalId): void;
}
