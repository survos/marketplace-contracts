<?php

declare(strict_types=1);

namespace Survos\MarketplaceContracts\Exception;

/** Something was wrong before any request was made: bad money, bad URL, missing option. */
final class MarketplaceConfigurationException extends \InvalidArgumentException implements MarketplaceException
{
}
