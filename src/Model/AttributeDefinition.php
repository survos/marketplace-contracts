<?php

declare(strict_types=1);

namespace Survos\MarketplaceContracts\Model;

/**
 * One attribute a category expects -- eBay calls these "item aspects", Mercado
 * Libre calls them "attributes". Same idea, same shape.
 */
final readonly class AttributeDefinition
{
    /**
     * @param list<string> $allowedValues closed value set; empty means free text
     * @param int|null     $maxValues     null = unlimited; 1 = single-valued
     */
    public function __construct(
        public string $name,
        public bool $required = false,
        public AttributeValueType $valueType = AttributeValueType::String,
        public array $allowedValues = [],
        public ?int $maxValues = 1,
        public ?string $hint = null,
    ) {
    }

    public function isClosedSet(): bool
    {
        return $this->allowedValues !== [];
    }

    public function isMultiValued(): bool
    {
        return $this->maxValues === null || $this->maxValues > 1;
    }

    public function allows(string $value): bool
    {
        if (!$this->isClosedSet()) {
            return true;
        }

        foreach ($this->allowedValues as $allowed) {
            if (strcasecmp($allowed, $value) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * This attribute as a JSON Schema property.
     *
     * @return array<string, mixed>
     */
    public function toJsonSchemaProperty(): array
    {
        $scalar = ['type' => $this->valueType->jsonSchemaType()];

        if ($this->isClosedSet()) {
            $scalar['enum'] = $this->allowedValues;
        }
        if ($this->hint !== null && $this->hint !== '') {
            $scalar['description'] = $this->hint;
        }

        if (!$this->isMultiValued()) {
            return $scalar;
        }

        $array = ['type' => 'array', 'items' => $scalar];
        if ($this->maxValues !== null) {
            $array['maxItems'] = $this->maxValues;
        }

        return $array;
    }
}
