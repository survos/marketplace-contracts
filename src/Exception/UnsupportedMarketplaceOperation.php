<?php

declare(strict_types=1);

namespace Survos\MarketplaceContracts\Exception;

use Survos\MarketplaceContracts\Model\ProviderCapability;

/**
 * The adapter cannot do this, and no retry will help.
 *
 * Thrown rather than silently degrading -- an adapter that quietly ignores a
 * sandbox request and publishes to production has done real harm.
 */
final class UnsupportedMarketplaceOperation extends \LogicException implements MarketplaceException
{
    public static function capability(string $provider, ProviderCapability $capability): self
    {
        return new self(sprintf(
            'The %s adapter does not support %s. Check capabilities() before calling.',
            $provider,
            $capability->value,
        ));
    }

    public static function noSandbox(string $provider): self
    {
        return new self(sprintf(
            'The %s adapter has no sandbox. Mercado Libre, in particular, has no test '
            . 'environment at all -- create test users against production instead (up to 10 '
            . 'per account, permanently undeletable, and test listings must be titled '
            . '"Test item - Do not offer").',
            $provider,
        ));
    }
}
