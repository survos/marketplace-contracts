<?php

declare(strict_types=1);

namespace Survos\MarketplaceContracts\Exception;

/**
 * The stored token is unusable and re-consent is needed.
 *
 * Distinct from a transient failure on purpose: the fix is a human visiting a
 * consent URL, so retrying is pointless. On Mercado Libre this is the symptom of
 * a refresh token that was reused after rotation.
 */
final class MarketplaceAuthorizationException extends \RuntimeException implements MarketplaceException
{
}
