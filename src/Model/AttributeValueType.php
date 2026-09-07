<?php

declare(strict_types=1);

namespace Survos\MarketplaceContracts\Model;

/**
 * The value type a marketplace expects for a category attribute.
 *
 * Maps onto both eBay item aspects (`aspectDataType`) and Mercado Libre category
 * attributes (`value_type`), which agree more than they disagree.
 */
enum AttributeValueType: string
{
    case String = 'string';
    case Number = 'number';
    case Boolean = 'boolean';
    case Date = 'date';

    /**
     * The JSON Schema primitive for this type, used when handing a category's
     * requirements to a language model.
     */
    public function jsonSchemaType(): string
    {
        return match ($this) {
            self::String, self::Date => 'string',
            self::Number => 'number',
            self::Boolean => 'boolean',
        };
    }
}
