<?php

declare(strict_types=1);

namespace Survos\MarketplaceContracts\Model;

/**
 * The result of a successful publish.
 *
 * `raw` keeps the provider's own response. Adapters normalize what they can, but a
 * seller chasing a listing problem needs the untouched payload, and discarding it
 * means the only copy is in a log somewhere.
 */
final readonly class PublishedListing
{
    /** @param array<string, mixed> $raw */
    public function __construct(
        public string $provider,
        public string $externalId,
        public string $sku,
        public ?string $url = null,
        public ?\DateTimeImmutable $publishedAt = null,
        public array $raw = [],
    ) {
    }
}
