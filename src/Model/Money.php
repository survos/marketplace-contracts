<?php

declare(strict_types=1);

namespace Survos\MarketplaceContracts\Model;

use Survos\MarketplaceContracts\Exception\MarketplaceConfigurationException;

/**
 * An exact monetary amount, held in minor units.
 *
 * Never a float. Marketplace prices are compared, summed, and echoed back to
 * sellers; binary floating point loses cents and the loss shows up in a listing.
 *
 * The scale is explicit rather than assumed. USD, MXN, BRL and ARS are all
 * exponent 2, which covers eBay US and Mercado Libre's Spanish/Portuguese sites,
 * but JPY is 0 and there is no reason to bake the assumption in.
 */
final readonly class Money implements \Stringable
{
    private function __construct(
        public int $minorUnits,
        public string $currency,
        public int $exponent = 2,
    ) {
    }

    /**
     * @param int $minorUnits e.g. 400 for $4.00
     */
    public static function ofMinorUnits(int $minorUnits, string $currency, int $exponent = 2): self
    {
        return new self($minorUnits, self::normalizeCurrency($currency), self::assertExponent($exponent));
    }

    /**
     * Parse a decimal string such as "4.00" or "0.78".
     *
     * A string, not a float, so the caller cannot silently hand over an amount
     * that was already wrong before it arrived.
     *
     * @throws MarketplaceConfigurationException on a malformed amount, or on more
     *                                           precision than the currency has
     */
    public static function fromDecimal(string $amount, string $currency, int $exponent = 2): self
    {
        $exponent = self::assertExponent($exponent);
        $trimmed = trim($amount);

        if (!preg_match('/^(?<sign>-?)(?<whole>\d+)(?:\.(?<fraction>\d+))?$/', $trimmed, $m)) {
            throw new MarketplaceConfigurationException(
                sprintf('"%s" is not a decimal amount.', $amount),
            );
        }

        $fraction = $m['fraction'] ?? '';
        if (strlen($fraction) > $exponent) {
            throw new MarketplaceConfigurationException(sprintf(
                '"%s" has %d decimal places but %s has %d. Round before constructing Money '
                . 'so the rounding is a decision someone made, not one this class made quietly.',
                $amount,
                strlen($fraction),
                strtoupper($currency),
                $exponent,
            ));
        }

        $minor = (int) ($m['whole'] . str_pad($fraction, $exponent, '0'));

        return new self($m['sign'] === '-' ? -$minor : $minor, self::normalizeCurrency($currency), $exponent);
    }

    /**
     * The wire format both eBay and Mercado Libre want: a plain decimal string.
     */
    public function toDecimal(): string
    {
        $sign = $this->minorUnits < 0 ? '-' : '';
        $abs = abs($this->minorUnits);

        if ($this->exponent === 0) {
            return $sign . $abs;
        }

        $divisor = 10 ** $this->exponent;

        return sprintf('%s%d.%s', $sign, intdiv($abs, $divisor), str_pad((string) ($abs % $divisor), $this->exponent, '0', STR_PAD_LEFT));
    }

    public function isZero(): bool
    {
        return $this->minorUnits === 0;
    }

    public function isNegative(): bool
    {
        return $this->minorUnits < 0;
    }

    public function plus(self $other): self
    {
        $this->assertComparable($other);

        return new self($this->minorUnits + $other->minorUnits, $this->currency, $this->exponent);
    }

    public function multipliedBy(int $factor): self
    {
        return new self($this->minorUnits * $factor, $this->currency, $this->exponent);
    }

    public function isGreaterThan(self $other): bool
    {
        $this->assertComparable($other);

        return $this->minorUnits > $other->minorUnits;
    }

    public function equals(self $other): bool
    {
        return $this->minorUnits === $other->minorUnits
            && $this->currency === $other->currency
            && $this->exponent === $other->exponent;
    }

    public function __toString(): string
    {
        return $this->toDecimal() . ' ' . $this->currency;
    }

    private function assertComparable(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new MarketplaceConfigurationException(sprintf(
                'Cannot combine %s with %s. Convert first; this class does not hold exchange rates.',
                $this->currency,
                $other->currency,
            ));
        }
    }

    private static function normalizeCurrency(string $currency): string
    {
        $normalized = strtoupper(trim($currency));

        if (!preg_match('/^[A-Z]{3}$/', $normalized)) {
            throw new MarketplaceConfigurationException(
                sprintf('"%s" is not an ISO 4217 currency code.', $currency),
            );
        }

        return $normalized;
    }

    private static function assertExponent(int $exponent): int
    {
        if ($exponent < 0 || $exponent > 4) {
            throw new MarketplaceConfigurationException(
                sprintf('Currency exponent %d is out of range (0-4).', $exponent),
            );
        }

        return $exponent;
    }
}
