<?php

declare(strict_types=1);

namespace Survos\MarketplaceContracts\Model;

/**
 * What one category expects: its attributes, which are required, and which have
 * closed value sets.
 *
 * This class is the reason the abstraction is worth having. `publish()` differs so
 * much between providers that the two adapters share no code -- eBay is four calls
 * behind one-time policy and location setup, Mercado Libre is a single POST. But
 * "ask the provider what this category demands, then generate content that
 * satisfies it" is identical everywhere, and it is the step that decides whether a
 * listing publishes or bounces.
 *
 * {@see toJsonSchema()} exists so the answer can go straight into a structured-output
 * request. Generating a listing first and discovering the required attributes from a
 * rejection is the slow, expensive version of the same loop.
 */
final readonly class AttributeSchema implements \Countable
{
    /** @var array<string, AttributeDefinition> */
    public array $attributes;

    /** @param list<AttributeDefinition> $attributes */
    public function __construct(
        public string $categoryId,
        array $attributes = [],
    ) {
        $byName = [];
        foreach ($attributes as $attribute) {
            $byName[$attribute->name] = $attribute;
        }
        $this->attributes = $byName;
    }

    public function get(string $name): ?AttributeDefinition
    {
        return $this->attributes[$name] ?? null;
    }

    /** @return list<AttributeDefinition> */
    public function required(): array
    {
        return array_values(array_filter($this->attributes, static fn (AttributeDefinition $a): bool => $a->required));
    }

    /** @return list<AttributeDefinition> */
    public function optional(): array
    {
        return array_values(array_filter($this->attributes, static fn (AttributeDefinition $a): bool => !$a->required));
    }

    /** @return list<string> */
    public function requiredNames(): array
    {
        return array_map(static fn (AttributeDefinition $a): string => $a->name, $this->required());
    }

    public function count(): int
    {
        return count($this->attributes);
    }

    /**
     * Category-specific problems with a draft: missing required attributes, values
     * outside a closed set, too many values for a single-valued attribute.
     *
     * Attributes the schema does not mention are left alone. Providers routinely
     * accept extras, and rejecting them here would make this class the arbiter of
     * something it does not actually know.
     *
     * @return list<ListingViolation>
     */
    public function violationsFor(ListingDraft $draft): array
    {
        $violations = [];

        foreach ($this->attributes as $name => $definition) {
            $present = $draft->hasAttribute($name);

            if (!$present) {
                if ($definition->required) {
                    $violations[] = ListingViolation::missingRequiredAttribute($name);
                }

                continue;
            }

            $values = $draft->attribute($name);
            $values = is_array($values) ? $values : [$values];

            if (!$definition->isMultiValued() && count($values) > 1) {
                $violations[] = new ListingViolation(
                    ViolationCode::TooManyValues,
                    'attributes.' . $name,
                    sprintf('"%s" accepts a single value, got %d.', $name, count($values)),
                );
            }

            if ($definition->maxValues !== null && count($values) > $definition->maxValues) {
                $violations[] = new ListingViolation(
                    ViolationCode::TooManyValues,
                    'attributes.' . $name,
                    sprintf('"%s" accepts at most %d values, got %d.', $name, $definition->maxValues, count($values)),
                );
            }

            foreach ($values as $value) {
                if (!$definition->allows((string) $value)) {
                    $violations[] = ListingViolation::valueNotAllowed($name, (string) $value, $definition->allowedValues);
                }
            }
        }

        return $violations;
    }

    /**
     * A JSON Schema for this category's attributes, for structured model output.
     *
     * Pass it as the response schema when asking a model to fill in a listing's
     * attributes, so required fields and closed value sets are enforced at
     * generation time instead of checked afterwards.
     *
     * @return array<string, mixed>
     */
    public function toJsonSchema(): array
    {
        $properties = [];
        foreach ($this->attributes as $name => $definition) {
            $properties[$name] = $definition->toJsonSchemaProperty();
        }

        $schema = [
            'type' => 'object',
            'description' => sprintf('Item attributes for marketplace category %s.', $this->categoryId),
            'properties' => $properties,
            'additionalProperties' => false,
        ];

        $required = $this->requiredNames();
        if ($required !== []) {
            $schema['required'] = $required;
        }

        return $schema;
    }

    /**
     * A compact human/prompt-readable rendering, for when a full JSON Schema is more
     * than the situation needs.
     */
    public function describe(): string
    {
        if ($this->attributes === []) {
            return sprintf('Category %s declares no attributes.', $this->categoryId);
        }

        $lines = [];
        foreach ($this->attributes as $name => $definition) {
            $parts = [$definition->required ? 'required' : 'optional'];
            if ($definition->isClosedSet()) {
                $shown = array_slice($definition->allowedValues, 0, 6);
                $more = count($definition->allowedValues) - count($shown);
                $parts[] = 'one of: ' . implode(', ', $shown) . ($more > 0 ? sprintf(' (+%d more)', $more) : '');
            } else {
                $parts[] = $definition->valueType->value;
            }
            if ($definition->isMultiValued()) {
                $parts[] = 'multi-valued';
            }

            $lines[] = sprintf('- %s (%s)', $name, implode('; ', $parts));
        }

        return implode("\n", $lines);
    }
}
