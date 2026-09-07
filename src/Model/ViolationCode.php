<?php

declare(strict_types=1);

namespace Survos\MarketplaceContracts\Model;

enum ViolationCode: string
{
    case MissingRequiredAttribute = 'missing_required_attribute';
    case ValueNotAllowed = 'value_not_allowed';
    case TooManyValues = 'too_many_values';
    case TitleTooLong = 'title_too_long';
    case DescriptionTooLong = 'description_too_long';
    case TooManyImages = 'too_many_images';
    case NoImages = 'no_images';
    case CategoryMissing = 'category_missing';
    case PriceInvalid = 'price_invalid';
    case QuantityInvalid = 'quantity_invalid';
    case SkuMissing = 'sku_missing';
}
