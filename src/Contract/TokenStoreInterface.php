<?php

declare(strict_types=1);

namespace Survos\MarketplaceContracts\Contract;

use Survos\MarketplaceContracts\Model\MarketplaceToken;

/**
 * Persistence for per-account OAuth tokens.
 *
 * IMPLEMENTATION REQUIREMENT: {@see save()} must overwrite the refresh token, not
 * preserve an existing one. Mercado Libre's refresh tokens are single-use and
 * rotate on every refresh; a write-once store passes every eBay test and then
 * locks the Mercado Libre account out permanently on its first refresh.
 *
 * Persist the replacement in the same transaction that consumes the predecessor.
 * If the process dies between the provider issuing a new refresh token and this
 * store committing it, the account needs re-consent -- there is no way to recover
 * the token, so the write must not be deferred.
 */
interface TokenStoreInterface
{
    public function get(string $connectionName): ?MarketplaceToken;

    /**
     * Store the token, replacing any predecessor in full.
     */
    public function save(string $connectionName, MarketplaceToken $token): void;

    public function delete(string $connectionName): void;
}
