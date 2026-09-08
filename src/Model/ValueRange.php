<?php

declare(strict_types=1);

namespace Survos\MarketplaceContracts\Model;

use Survos\MarketplaceContracts\Exception\MarketplaceConfigurationException;

/**
 * What something is worth, as a range.
 *
 * Distinct from a price, and the distinction is the point: a price is what someone
 * decided to ask, a value is an estimate of what the thing is worth. Collapsing the
 * two loses the ability to tell a considered price from a placeholder -- which is
 * exactly the state a listing is in when it carries a round number nobody chose.
 *
 * A range rather than a point because the honest answer usually is one. A postcard's
 * worth turns on condition, postmark, publisher and rarity, most of which are not
 * legible in a photograph; a single number invents precision that the evidence does
 * not support. A point estimate is still expressible -- low and high are equal --
 * so nothing needs a special case for it.
 */
final readonly class ValueRange implements \Stringable
{
    private function __construct(
        public Money $low,
        public Money $high,
    ) {
    }

    /**
     * @throws MarketplaceConfigurationException when the bounds disagree on currency,
     *                                           or arrive in the wrong order
     */
    public static function between(Money $low, Money $high): self
    {
        if ($low->currency !== $high->currency) {
            throw new MarketplaceConfigurationException(sprintf(
                'A value range needs one currency, got %s and %s.',
                $low->currency,
                $high->currency,
            ));
        }

        // Swapping silently would hide whichever upstream bug produced the pair --
        // an AI estimate with the bounds reversed is a signal worth surfacing.
        if ($low->minorUnits > $high->minorUnits) {
            throw new MarketplaceConfigurationException(sprintf(
                'Value range is inverted: low %s is above high %s.',
                $low,
                $high,
            ));
        }

        return new self($low, $high);
    }

    /** A range that happens to be certain, or an estimate that arrived as one number. */
    public static function exactly(Money $amount): self
    {
        return new self($amount, $amount);
    }

    /**
     * Parse the pair the way Money parses one: decimal strings, never floats, so a
     * bad amount cannot arrive already rounded.
     *
     * @throws MarketplaceConfigurationException
     */
    public static function fromDecimals(string $low, string $high, string $currency, int $exponent = 2): self
    {
        return self::between(
            Money::fromDecimal($low, $currency, $exponent),
            Money::fromDecimal($high, $currency, $exponent),
        );
    }

    public function currency(): string
    {
        return $this->low->currency;
    }

    public function isPoint(): bool
    {
        return $this->low->minorUnits === $this->high->minorUnits;
    }

    /**
     * The middle of the range, rounded down to a whole minor unit.
     *
     * Offered because something eventually has to propose a number, but deliberately
     * not named `price`: turning a value into an asking price is a decision, and this
     * is only the arithmetic under it.
     */
    public function midpoint(): Money
    {
        return Money::ofMinorUnits(
            intdiv($this->low->minorUnits + $this->high->minorUnits, 2),
            $this->low->currency,
            $this->low->exponent,
        );
    }

    public function equals(self $other): bool
    {
        return $this->low->equals($other->low) && $this->high->equals($other->high);
    }

    public function __toString(): string
    {
        return $this->isPoint()
            ? (string) $this->low
            : sprintf('%s–%s', $this->low->toDecimal(), (string) $this->high);
    }
}
