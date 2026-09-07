<?php

declare(strict_types=1);

namespace Survos\MarketplaceContracts\Registry;

use Survos\MarketplaceContracts\Contract\AdapterFactoryInterface;
use Survos\MarketplaceContracts\Contract\MarketplaceAdapterInterface;
use Survos\MarketplaceContracts\Exception\MarketplaceConfigurationException;
use Survos\MarketplaceContracts\Model\ConnectionConfiguration;

/**
 * Resolves a connection to an adapter by driver name.
 *
 * Plain PHP, no container: survos/marketplace-bundle wires the factories in for
 * Symfony consumers, and a WordPress or CLI caller constructs it directly.
 */
final class MarketplaceRegistry
{
    /** @var list<AdapterFactoryInterface> */
    private array $factories;

    /** @param iterable<AdapterFactoryInterface> $factories */
    public function __construct(iterable $factories = [])
    {
        $this->factories = is_array($factories) ? array_values($factories) : iterator_to_array($factories, false);
    }

    public function add(AdapterFactoryInterface $factory): void
    {
        $this->factories[] = $factory;
    }

    /** @return list<string> */
    public function drivers(): array
    {
        return array_map(
            static fn (AdapterFactoryInterface $factory): string => $factory->driver(),
            $this->factories,
        );
    }

    public function has(string $driver): bool
    {
        foreach ($this->factories as $factory) {
            if ($factory->supports($driver)) {
                return true;
            }
        }

        return false;
    }

    public function create(ConnectionConfiguration $connection): MarketplaceAdapterInterface
    {
        foreach ($this->factories as $factory) {
            if ($factory->supports($connection->driver)) {
                return $factory->create($connection);
            }
        }

        throw new MarketplaceConfigurationException(sprintf(
            'No adapter factory handles driver "%s". Registered: %s.',
            $connection->driver,
            $this->factories === [] ? '(none)' : implode(', ', $this->drivers()),
        ));
    }
}
