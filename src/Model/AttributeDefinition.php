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
     * @param list<string> $allowedValues a CLOSED set -- the value must be one of these.
     *                                    Empty means free text.
     * @param list<string> $examples      values the provider suggests but does not require.
     *
     * The distinction is not pedantic. Both marketplaces return sample values for
     * free-text attributes, and both flag the difference: eBay via
     * `aspectMode: SELECTION_ONLY` vs `FREE_TEXT`, Mercado Libre via
     * `value_type: list` vs anything else. Treating a sample as a closed set is
     * actively harmful -- Mercado Libre offers "España, Francia" as ORIGIN examples
     * for a Mexican postcard category, and an enum built from that forces a model to
     * mislabel a Chiapas postcard as Spanish. Confidently wrong beats absent only
     * from the model's point of view.
     *
     * @param int|null $maxValues null = unlimited; 1 = single-valued
     */
    public function __construct(
        public string $name,
        public bool $required = false,
        public AttributeValueType $valueType = AttributeValueType::String,
        public array $allowedValues = [],
        public ?int $maxValues = 1,
        public ?string $hint = null,
        public array $examples = [],
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
        } elseif ($this->examples !== []) {
            // examples, not enum: a model may follow them, but is not confined to them.
            $scalar['examples'] = $this->examples;
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
