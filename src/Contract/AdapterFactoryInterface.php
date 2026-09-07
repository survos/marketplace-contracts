<?php

declare(strict_types=1);

namespace Survos\MarketplaceContracts\Contract;

use Survos\MarketplaceContracts\Model\ConnectionConfiguration;

interface AdapterFactoryInterface
{
    /** Canonical driver id this factory produces, e.g. `ebay`. */
    public function driver(): string;

    /** True for {@see driver()} and for any alias the factory also answers to. */
    public function supports(string $driver): bool;

    public function create(ConnectionConfiguration $connection): MarketplaceAdapterInterface;
}
