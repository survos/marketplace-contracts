<?php

declare(strict_types=1);

namespace Survos\MarketplaceContracts\Contract;

use Survos\MarketplaceContracts\Model\AttributeSchema;
use Survos\MarketplaceContracts\Model\CategorySuggestion;

/**
 * Turn a free-text title into a category, then report what that category demands.
 *
 * Split out from publishing because it is the half that genuinely generalizes, and
 * because it is useful on its own -- a caller can price and validate against a
 * category without ever publishing.
 */
interface CategoryResolverInterface
{
    /**
     * @param string $title  free text, typically the AI-generated listing title
     * @param int    $limit  maximum suggestions to return, best first
     *
     * @return list<CategorySuggestion> empty when the provider has no opinion
     */
    public function suggestCategories(string $title, int $limit = 5): array;

    /**
     * The attributes this category expects.
     *
     * Feed {@see AttributeSchema::toJsonSchema()} to a model as a response schema so
     * required attributes are satisfied at generation time.
     */
    public function attributeSchema(string $categoryId): AttributeSchema;
}
