<?php

declare(strict_types=1);

namespace Survos\MarketplaceContracts\Model;

/**
 * Provider-neutral item condition.
 *
 * The granularity is eBay's, because eBay's is finer: Mercado Libre only
 * distinguishes new / used / unspecified. Coarsening is lossless in the direction
 * adapters actually need it, whereas starting from ML's three values would make
 * an eBay listing unable to say "Very Good".
 *
 * Deliberately carries no provider codes. eBay's numeric condition IDs and ML's
 * string values live in their adapters, so adding a third marketplace does not
 * mean editing this enum.
 */
enum ListingCondition: string
{
    case New = 'new';
    case NewOther = 'new_other';
    case LikeNew = 'like_new';
    case UsedExcellent = 'used_excellent';
    case UsedVeryGood = 'used_very_good';
    case UsedGood = 'used_good';
    case UsedAcceptable = 'used_acceptable';
    case ForPartsOrNotWorking = 'for_parts_or_not_working';

    public function isNew(): bool
    {
        return $this === self::New || $this === self::NewOther;
    }

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::NewOther => 'New (other)',
            self::LikeNew => 'Like new',
            self::UsedExcellent => 'Used - excellent',
            self::UsedVeryGood => 'Used - very good',
            self::UsedGood => 'Used - good',
            self::UsedAcceptable => 'Used - acceptable',
            self::ForPartsOrNotWorking => 'For parts or not working',
        };
    }
}
