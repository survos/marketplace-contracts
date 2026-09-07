<?php

declare(strict_types=1);

namespace Survos\MarketplaceContracts\Tests;

use PHPUnit\Framework\TestCase;
use Survos\MarketplaceContracts\Model\ListingDraft;
use Survos\MarketplaceContracts\Model\ListingImage;
use Survos\MarketplaceContracts\Model\MarketplaceLimits;
use Survos\MarketplaceContracts\Model\Money;
use Survos\MarketplaceContracts\Model\ViolationCode;

final class ListingDraftTest extends TestCase
{
    private function draft(): ListingDraft
    {
        return new ListingDraft(
            sku: 'PC-ANIMALS-001',
            title: 'Lot of 5 Vintage Animal Postcards',
            description: 'Five themed postcards.',
            price: Money::fromDecimal('4.00', 'USD'),
            categoryId: '914',
            images: [new ListingImage('https://example.org/a.jpg')],
        );
    }

    public function testCompleteDraftHasNoViolations(): void
    {
        self::assertSame([], $this->draft()->violations());
    }

    public function testFlagsMissingCategory(): void
    {
        $draft = new ListingDraft(
            sku: 'X',
            title: 'T',
            description: 'D',
            price: Money::fromDecimal('4.00', 'USD'),
        );

        $codes = array_map(static fn ($v) => $v->code, $draft->violations());
        self::assertContains(ViolationCode::CategoryMissing, $codes);
    }

    public function testFlagsZeroPrice(): void
    {
        $draft = $this->draft()->withPrice(Money::fromDecimal('0', 'USD'));

        $codes = array_map(static fn ($v) => $v->code, $draft->violations());
        self::assertContains(ViolationCode::PriceInvalid, $codes);
    }

    public function testEnforcesEbayTitleCapWhenLimitsAreSupplied(): void
    {
        $limits = new MarketplaceLimits(titleMaxLength: 80, maxImages: 24);

        $long = $this->draft()->withTitle(str_repeat('a', 81));
        $codes = array_map(static fn ($v) => $v->code, $long->violations($limits));

        self::assertContains(ViolationCode::TitleTooLong, $codes);
        self::assertSame([], $this->draft()->violations($limits));
    }

    public function testTitleCapCountsCharactersNotBytes(): void
    {
        $limits = new MarketplaceLimits(titleMaxLength: 80, maxImages: 24);

        // 80 accented characters is 160 bytes but a legal eBay title.
        $draft = $this->draft()->withTitle(str_repeat('é', 80));

        self::assertSame([], $draft->violations($limits));
    }

    public function testFlagsMissingImages(): void
    {
        $limits = new MarketplaceLimits(titleMaxLength: 80, maxImages: 24);
        $draft = $this->draft()->withImages([]);

        $codes = array_map(static fn ($v) => $v->code, $draft->violations($limits));
        self::assertContains(ViolationCode::NoImages, $codes);
    }

    public function testWithersDoNotMutateTheOriginal(): void
    {
        $original = $this->draft();
        $changed = $original->withTitle('Something else')->withCategoryId('999');

        self::assertSame('Lot of 5 Vintage Animal Postcards', $original->title);
        self::assertSame('914', $original->categoryId);
        self::assertSame('Something else', $changed->title);
        self::assertSame('999', $changed->categoryId);
    }

    public function testWithAttributesMergesRatherThanReplaces(): void
    {
        $draft = $this->draft()
            ->withAttributes(['Era' => 'Pre-1914'])
            ->withAttributes(['Theme' => 'Animals']);

        self::assertSame('Pre-1914', $draft->attribute('Era'));
        self::assertSame('Animals', $draft->attribute('Theme'));
    }

    public function testRejectsNonHttpsImages(): void
    {
        $this->expectException(\Survos\MarketplaceContracts\Exception\MarketplaceConfigurationException::class);

        new ListingImage('http://example.org/a.jpg');
    }
}
