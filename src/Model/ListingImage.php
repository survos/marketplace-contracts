<?php

declare(strict_types=1);

namespace Survos\MarketplaceContracts\Model;

use Survos\MarketplaceContracts\Exception\MarketplaceConfigurationException;

/**
 * An image to attach to a listing, always as a URL.
 *
 * Neither eBay's Inventory API nor Mercado Libre accepts a binary upload on the
 * listing call, so a URL is the only shape that is portable. eBay requires the URL
 * to stay reachable; Mercado Libre fetches it once and re-hosts.
 */
final readonly class ListingImage implements \Stringable
{
    public function __construct(
        public string $url,
        public ?string $caption = null,
    ) {
        if (!str_starts_with($url, 'https://')) {
            throw new MarketplaceConfigurationException(sprintf(
                'Listing image URLs must be https, got "%s". eBay rejects plain http outright '
                . 'and Mercado Libre will not fetch it.',
                $url,
            ));
        }
    }

    public function __toString(): string
    {
        return $this->url;
    }
}
