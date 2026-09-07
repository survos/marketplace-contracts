<?php

declare(strict_types=1);

namespace Survos\MarketplaceContracts\Model;

/**
 * Things a marketplace adapter may or may not be able to do.
 *
 * Callers branch on these rather than on the provider name, so a new adapter does
 * not require editing every consumer.
 */
enum ProviderCapability: string
{
    /** A real sandbox exists. eBay yes; Mercado Libre no (test users in production instead). */
    case Sandbox = 'sandbox';

    /** Category can be predicted from a free-text title. */
    case CategorySuggestion = 'category_suggestion';

    /** Per-category required/optional attributes can be enumerated. */
    case AttributeSchema = 'attribute_schema';

    /** A published listing can be withdrawn through the API. */
    case Withdraw = 'withdraw';

    /** Shipping cost can be set per listing (first item + each additional). */
    case FlatShipping = 'flat_shipping';

    /** The provider computes shipping from weight/dimensions and buyer location. */
    case CalculatedShipping = 'calculated_shipping';

    /**
     * A seller must be bootstrapped (policies, locations) before publishing.
     * True for eBay, false for Mercado Libre.
     */
    case SellerBootstrap = 'seller_bootstrap';

    /** Images are supplied as URLs the provider fetches and re-hosts. */
    case RemoteImageIngest = 'remote_image_ingest';
}
