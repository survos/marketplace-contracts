<?php

declare(strict_types=1);

namespace Survos\MarketplaceContracts\Model;

/**
 * Everything needed to publish one listing, in provider-neutral terms.
 *
 * Immutable, with `with*()` returning copies, because the pipeline that builds one
 * is progressive and partly machine-driven: an AI pass produces a title and price,
 * a category lookup fills in `categoryId`, an attribute lookup tells a second AI
 * pass what the category demands, and only then is the draft complete. Mutating in
 * place would make it impossible to show a human what changed at each step, or to
 * retry one step without rerunning the rest.
 *
 * A draft carries no provider identity. The same draft can be offered to two
 * adapters, which is the point.
 */
final readonly class ListingDraft
{
    /**
     * @param string                               $sku        seller's own identifier; eBay keys inventory on it
     * @param list<ListingImage>                   $images
     * @param array<string, string|list<string>>   $attributes category attributes, keyed by name
     * @param string                               $locale     BCP 47, e.g. `en-US`, `es-MX`
     */
    public function __construct(
        public string $sku,
        public string $title,
        public string $description,
        public Money $price,
        public ListingCondition $condition = ListingCondition::UsedGood,
        public int $quantity = 1,
        public ?string $categoryId = null,
        public array $images = [],
        public array $attributes = [],
        public ?ShippingSpec $shipping = null,
        public ListingFormat $format = ListingFormat::FixedPrice,
        public string $locale = 'en-US',
    ) {
    }

    public function withCategoryId(string $categoryId): self
    {
        return $this->with(categoryId: $categoryId);
    }

    public function withTitle(string $title): self
    {
        return $this->with(title: $title);
    }

    public function withPrice(Money $price): self
    {
        return $this->with(price: $price);
    }

    public function withShipping(ShippingSpec $shipping): self
    {
        return $this->with(shipping: $shipping);
    }

    /** @param list<ListingImage> $images */
    public function withImages(array $images): self
    {
        return $this->with(images: $images);
    }

    /**
     * Merge attributes over the existing ones. Existing keys win only if absent
     * from $attributes.
     *
     * @param array<string, string|list<string>> $attributes
     */
    public function withAttributes(array $attributes): self
    {
        return $this->with(attributes: [...$this->attributes, ...$attributes]);
    }

    /** @return string|list<string>|null */
    public function attribute(string $name): string|array|null
    {
        return $this->attributes[$name] ?? null;
    }

    public function hasAttribute(string $name): bool
    {
        $value = $this->attributes[$name] ?? null;

        return $value !== null && $value !== '' && $value !== [];
    }

    /**
     * Structural checks that hold on every marketplace. Category-specific rules
     * live in {@see AttributeSchema::violationsFor()}; provider caps come from
     * {@see MarketplaceLimits}, passed here when available.
     *
     * @return list<ListingViolation>
     */
    public function violations(?MarketplaceLimits $limits = null): array
    {
        $violations = [];

        if (trim($this->sku) === '') {
            $violations[] = new ListingViolation(ViolationCode::SkuMissing, 'sku', 'SKU is required.');
        }
        if ($this->categoryId === null || $this->categoryId === '') {
            $violations[] = new ListingViolation(
                ViolationCode::CategoryMissing,
                'categoryId',
                'No category. Resolve one with suggestCategory() before publishing.',
            );
        }
        if ($this->quantity < 1) {
            $violations[] = new ListingViolation(
                ViolationCode::QuantityInvalid,
                'quantity',
                sprintf('Quantity must be at least 1, got %d.', $this->quantity),
            );
        }
        if ($this->price->isNegative() || $this->price->isZero()) {
            $violations[] = new ListingViolation(
                ViolationCode::PriceInvalid,
                'price',
                sprintf('Price must be greater than zero, got %s.', $this->price),
            );
        }

        if ($limits === null) {
            return $violations;
        }

        $titleLength = mb_strlen($this->title);
        if ($titleLength > $limits->titleMaxLength) {
            $violations[] = new ListingViolation(
                ViolationCode::TitleTooLong,
                'title',
                sprintf(
                    'Title is %d characters; the limit is %d. Regenerate within the limit rather than '
                    . 'truncating -- a title cut mid-word reads as broken.',
                    $titleLength,
                    $limits->titleMaxLength,
                ),
            );
        }

        if ($limits->descriptionMaxLength !== null && mb_strlen($this->description) > $limits->descriptionMaxLength) {
            $violations[] = new ListingViolation(
                ViolationCode::DescriptionTooLong,
                'description',
                sprintf('Description exceeds %d characters.', $limits->descriptionMaxLength),
            );
        }

        if (count($this->images) < $limits->minImages) {
            $violations[] = new ListingViolation(
                ViolationCode::NoImages,
                'images',
                sprintf('At least %d image is required, got %d.', $limits->minImages, count($this->images)),
            );
        }
        if (count($this->images) > $limits->maxImages) {
            $violations[] = new ListingViolation(
                ViolationCode::TooManyImages,
                'images',
                sprintf('At most %d images are allowed, got %d.', $limits->maxImages, count($this->images)),
            );
        }

        return $violations;
    }

    /**
     * @param list<ListingImage>|null                 $images
     * @param array<string, string|list<string>>|null $attributes
     */
    private function with(
        ?string $sku = null,
        ?string $title = null,
        ?string $description = null,
        ?Money $price = null,
        ?ListingCondition $condition = null,
        ?int $quantity = null,
        ?string $categoryId = null,
        ?array $images = null,
        ?array $attributes = null,
        ?ShippingSpec $shipping = null,
        ?ListingFormat $format = null,
        ?string $locale = null,
    ): self {
        return new self(
            sku: $sku ?? $this->sku,
            title: $title ?? $this->title,
            description: $description ?? $this->description,
            price: $price ?? $this->price,
            condition: $condition ?? $this->condition,
            quantity: $quantity ?? $this->quantity,
            categoryId: $categoryId ?? $this->categoryId,
            images: $images ?? $this->images,
            attributes: $attributes ?? $this->attributes,
            shipping: $shipping ?? $this->shipping,
            format: $format ?? $this->format,
            locale: $locale ?? $this->locale,
        );
    }
}
