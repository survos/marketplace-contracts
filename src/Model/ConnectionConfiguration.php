<?php

declare(strict_types=1);

namespace Survos\MarketplaceContracts\Model;

use Survos\MarketplaceContracts\Exception\MarketplaceConfigurationException;

/**
 * How to reach one marketplace account.
 *
 * Mirrors survos/record-store's ConnectionConfiguration: a driver name plus an
 * opaque options bag the adapter interprets. Credentials are NOT held here --
 * tokens live behind {@see \Survos\MarketplaceContracts\Contract\TokenStoreInterface},
 * because they rotate and this object does not.
 */
final readonly class ConnectionConfiguration
{
    /**
     * @param string               $name    caller's label for this account, e.g. `dave-postcards`
     * @param string               $driver  adapter selector, e.g. `ebay`, `mercadolibre`
     * @param string               $site    provider marketplace/site id, e.g. `EBAY_US`, `MLM`
     * @param bool                 $sandbox use the provider's sandbox. Meaningless where
     *                                      {@see ProviderCapability::Sandbox} is absent --
     *                                      Mercado Libre has none, and an adapter without the
     *                                      capability must reject this rather than pretend.
     * @param array<string, mixed> $options driver-specific settings
     */
    public function __construct(
        public string $name,
        public string $driver,
        public string $site,
        public bool $sandbox = false,
        public array $options = [],
    ) {
        if (trim($name) === '' || trim($driver) === '' || trim($site) === '') {
            throw new MarketplaceConfigurationException('name, driver and site are all required.');
        }
    }

    public function option(string $key, mixed $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }

    /** @throws MarketplaceConfigurationException when the option is absent or empty */
    public function requiredOption(string $key): mixed
    {
        $value = $this->options[$key] ?? null;

        if ($value === null || $value === '') {
            throw new MarketplaceConfigurationException(sprintf(
                'Connection "%s" (%s) is missing required option "%s".',
                $this->name,
                $this->driver,
                $key,
            ));
        }

        return $value;
    }
}
