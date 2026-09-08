<?php

declare(strict_types=1);

namespace Survos\MarketplaceContracts\Model;

use Survos\MarketplaceContracts\Exception\MarketplaceConfigurationException;

/**
 * One estimate of what something is worth, for one situation.
 *
 * Carries its own confidence because the estimates worth having are the ones a
 * machine produced from a photograph, and those are frequently poor. Confidence is
 * what lets a caller decide whether to propose a number or leave the field empty for
 * a human -- a low-confidence guess written into a price field is indistinguishable
 * from a considered one the moment it is stored.
 *
 * `basis` exists for the same reason. An estimate a seller cannot interrogate is one
 * they can only accept or ignore.
 */
final readonly class ValueEstimate
{
    /**
     * @param float       $confidence 0.0–1.0
     * @param string|null $basis      short rationale, in the estimator's own words
     */
    private function __construct(
        public ValueSituation $situation,
        public ValueRange $range,
        public float $confidence,
        public ?string $basis = null,
    ) {
    }

    /**
     * @throws MarketplaceConfigurationException on a confidence outside 0..1
     */
    public static function of(
        ValueSituation $situation,
        ValueRange $range,
        float $confidence,
        ?string $basis = null,
    ): self {
        if ($confidence < 0.0 || $confidence > 1.0) {
            throw new MarketplaceConfigurationException(sprintf(
                'Confidence must be between 0 and 1, got %s.',
                var_export($confidence, true),
            ));
        }

        return new self($situation, $range, $confidence, self::trimToNull($basis));
    }

    /**
     * Whether this is solid enough to act on without a human looking first.
     *
     * The threshold is the caller's, not this class's: what counts as confident
     * enough to auto-fill a price differs between a $2 postcard and a $2,000 camera,
     * and nothing here knows which it is holding.
     */
    public function isConfidentAtLeast(float $threshold): bool
    {
        return $this->confidence >= $threshold;
    }

    private static function trimToNull(?string $value): ?string
    {
        $trimmed = trim((string) $value);

        return '' === $trimmed ? null : $trimmed;
    }
}
