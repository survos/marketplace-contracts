<?php

declare(strict_types=1);

namespace Survos\MarketplaceContracts\Tests;

use PHPUnit\Framework\TestCase;
use Survos\MarketplaceContracts\Model\AttributeDefinition;
use Survos\MarketplaceContracts\Model\AttributeSchema;
use Survos\MarketplaceContracts\Model\ListingDraft;
use Survos\MarketplaceContracts\Model\Money;
use Survos\MarketplaceContracts\Model\ViolationCode;

final class AttributeSchemaTest extends TestCase
{
    private function postcardSchema(): AttributeSchema
    {
        return new AttributeSchema('914', [
            new AttributeDefinition(name: 'Era', required: true, allowedValues: ['Pre-1914', '1914-1945', 'Post-1945']),
            new AttributeDefinition(name: 'Theme', required: true, maxValues: 3),
            new AttributeDefinition(name: 'Postage Condition', required: false, allowedValues: ['Posted', 'Unposted']),
        ]);
    }

    /** @param array<string, string|list<string>> $attributes */
    private function draft(array $attributes): ListingDraft
    {
        return new ListingDraft(
            sku: 'PC-ANIMALS-001',
            title: 'Lot of 5 Vintage Animal Postcards',
            description: 'Five themed postcards.',
            price: Money::fromDecimal('4.00', 'USD'),
            categoryId: '914',
            attributes: $attributes,
        );
    }

    public function testFlagsMissingRequiredAttributes(): void
    {
        $violations = $this->postcardSchema()->violationsFor($this->draft(['Era' => 'Pre-1914']));

        self::assertCount(1, $violations);
        self::assertSame(ViolationCode::MissingRequiredAttribute, $violations[0]->code);
        self::assertSame('attributes.Theme', $violations[0]->field);
    }

    public function testTreatsEmptyValuesAsMissing(): void
    {
        $violations = $this->postcardSchema()->violationsFor($this->draft(['Era' => '', 'Theme' => []]));

        self::assertCount(2, $violations);
    }

    public function testFlagsValueOutsideClosedSet(): void
    {
        $violations = $this->postcardSchema()->violationsFor($this->draft([
            'Era' => 'Victorian',
            'Theme' => 'Animals',
        ]));

        self::assertCount(1, $violations);
        self::assertSame(ViolationCode::ValueNotAllowed, $violations[0]->code);
        self::assertStringContainsString('Pre-1914', $violations[0]->message);
    }

    public function testClosedSetMatchIsCaseInsensitive(): void
    {
        $violations = $this->postcardSchema()->violationsFor($this->draft([
            'Era' => 'pre-1914',
            'Theme' => 'Animals',
        ]));

        self::assertSame([], $violations);
    }

    public function testFlagsTooManyValues(): void
    {
        $violations = $this->postcardSchema()->violationsFor($this->draft([
            'Era' => 'Pre-1914',
            'Theme' => ['Animals', 'Dogs', 'Cats', 'Birds'],
        ]));

        self::assertCount(1, $violations);
        self::assertSame(ViolationCode::TooManyValues, $violations[0]->code);
    }

    public function testIgnoresAttributesTheSchemaDoesNotMention(): void
    {
        $violations = $this->postcardSchema()->violationsFor($this->draft([
            'Era' => 'Pre-1914',
            'Theme' => 'Animals',
            'Publisher' => 'Raphael Tuck',
        ]));

        self::assertSame([], $violations);
    }

    public function testJsonSchemaCarriesRequiredNamesAndEnums(): void
    {
        $schema = $this->postcardSchema()->toJsonSchema();

        self::assertSame(['Era', 'Theme'], $schema['required']);
        self::assertSame(['Pre-1914', '1914-1945', 'Post-1945'], $schema['properties']['Era']['enum']);
        self::assertFalse($schema['additionalProperties']);

        // Multi-valued attributes become arrays with the cap expressed as maxItems,
        // so a model cannot generate four themes for a three-theme category.
        self::assertSame('array', $schema['properties']['Theme']['type']);
        self::assertSame(3, $schema['properties']['Theme']['maxItems']);

        self::assertNotEmpty(json_encode($schema, JSON_THROW_ON_ERROR));
    }

    public function testSingleValuedAttributeIsNotAnArrayInJsonSchema(): void
    {
        $schema = $this->postcardSchema()->toJsonSchema();

        self::assertSame('string', $schema['properties']['Era']['type']);
    }
}
