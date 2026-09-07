<?php

declare(strict_types=1);

namespace Survos\MarketplaceContracts\Model;

/**
 * A category a provider thinks a title belongs in.
 *
 * eBay reaches this through Taxonomy `get_category_suggestions`, Mercado Libre
 * through `domain_discovery/search`. Both return a ranked list, both give a full
 * ancestry path, and neither guarantees a confidence score -- hence nullable.
 */
final readonly class CategorySuggestion
{
    /**
     * @param string       $categoryId provider category id, opaque
     * @param list<string> $path       ancestry from root to leaf, for showing a human
     *                                 why this was picked
     * @param float|null   $confidence 0.0-1.0 when the provider gives one
     */
    public function __construct(
        public string $categoryId,
        public string $name,
        public array $path = [],
        public ?float $confidence = null,
    ) {
    }

    public function breadcrumb(string $separator = ' > '): string
    {
        return implode($separator, $this->path !== [] ? $this->path : [$this->name]);
    }
}
