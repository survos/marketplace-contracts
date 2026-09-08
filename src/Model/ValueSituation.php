<?php

declare(strict_types=1);

namespace Survos\MarketplaceContracts\Model;

/**
 * The circumstance an estimate is for.
 *
 * The same object is worth different amounts depending on how it is being sold, and
 * by margins large enough that a single figure is not merely imprecise but wrong: a
 * postcard worth $12 to a collector on Etsy is worth a dollar in a driveway, because
 * what is being priced is not only the object but the patience and reach of the sale.
 *
 * Closed rather than free text, because these drive routing -- which estimate a
 * listing draws on depends on where it is going.
 */
enum ValueSituation: string
{
    /** A patient sale to someone looking for this specific thing. Etsy, eBay. */
    case Resale = 'resale';

    /** Local, immediate, cash. Priced to move rather than to maximise. */
    case GarageSale = 'garageSale';

    /**
     * Replacement cost, which is not a sale price at all -- it is what the owner
     * would have to pay to get another one, and so runs higher than either.
     * Belongs to collection inventory rather than to any listing.
     */
    case Insurance = 'insurance';
}
