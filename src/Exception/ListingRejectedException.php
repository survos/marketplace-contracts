<?php

declare(strict_types=1);

namespace Survos\MarketplaceContracts\Exception;

use Survos\MarketplaceContracts\Model\ListingViolation;

/**
 * The provider refused the listing.
 *
 * Carries the violations rather than just a message, so a caller can feed them back
 * into whatever produced the draft and try again.
 */
final class ListingRejectedException extends \RuntimeException implements MarketplaceException
{
    /** @param list<ListingViolation> $violations */
    public function __construct(
        public readonly string $provider,
        public readonly array $violations,
        string $message = '',
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            $message !== '' ? $message : self::summarize($provider, $violations),
            0,
            $previous,
        );
    }

    /** @param list<ListingViolation> $violations */
    private static function summarize(string $provider, array $violations): string
    {
        if ($violations === []) {
            return sprintf('%s rejected the listing without saying why.', $provider);
        }

        return sprintf(
            "%s rejected the listing:\n%s",
            $provider,
            implode("\n", array_map(static fn (ListingViolation $v): string => '  ' . $v, $violations)),
        );
    }
}
