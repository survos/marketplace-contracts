<?php

declare(strict_types=1);

namespace Survos\MarketplaceContracts\Model;

/**
 * Hard limits an adapter publishes so callers can respect them *before* generating
 * content, rather than discovering them in a rejected publish.
 *
 * The title cap is the one that matters in practice. eBay's is 80 characters; a
 * language model asked for "a good listing title" will cheerfully produce 140.
 * Truncating afterwards gives a title that ends mid-word, so the cap belongs in
 * the generation prompt -- which means it has to be readable from here.
 */
final readonly class MarketplaceLimits
{
    public function __construct(
        public int $titleMaxLength,
        public int $maxImages,
        public ?int $descriptionMaxLength = null,
        public int $minImages = 1,
        public bool $allowsHtmlDescription = true,
    ) {
    }
}
