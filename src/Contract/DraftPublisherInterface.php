<?php

declare(strict_types=1);

namespace Survos\MarketplaceContracts\Contract;

use Survos\MarketplaceContracts\Model\PublishedListing;

/**
 * For marketplaces where a listing can exist before it is visible.
 *
 * Separate from MarketplaceAdapterInterface because not every marketplace has the
 * concept, and the ones that do disagree about what it means:
 *
 *   Etsy          createDraftListing is the ONLY way to create anything. Every
 *                 listing starts as a draft and someone has to activate it.
 *   eBay          an offer exists before publishOffer is called, so the same
 *                 two-step shape is available, though the API does not name it
 *                 "draft".
 *   Mercado Libre POST /items publishes immediately. There is no draft state, so
 *                 this interface is simply not implemented.
 *
 * Forcing that onto the main interface would give two of three adapters a method
 * that throws. Callers check with instanceof instead, which is honest about the
 * fact that "publish this draft" is not a universal operation.
 */
interface DraftPublisherInterface
{
    /**
     * Make an existing draft visible to buyers.
     *
     * The counterpart to a publish() that produced something invisible. Idempotent
     * where the provider allows it -- activating an already-active listing should
     * not be an error, because a double-click is not a mistake worth punishing.
     *
     * @param string $externalId the handle publish() returned
     */
    public function activate(string $externalId): PublishedListing;

    /** True when this listing is created invisible and needs activate() to go live. */
    public function createsDrafts(): bool;
}
