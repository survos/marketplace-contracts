<?php

declare(strict_types=1);

namespace Survos\MarketplaceContracts\Model;

enum ListingFormat: string
{
    case FixedPrice = 'fixed_price';
    case Auction = 'auction';
}
