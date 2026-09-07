<?php

declare(strict_types=1);

namespace Survos\MarketplaceContracts\Model;

/**
 * An OAuth token pair for one marketplace account.
 *
 * Both providers use the authorization-code grant, but they differ in a way that
 * silently breaks naive storage:
 *
 * | | eBay | Mercado Libre |
 * |---|---|---|
 * | access token | ~2h | 6h |
 * | refresh token | ~18 months, stable | 6 months, SINGLE USE |
 *
 * Mercado Libre issues a new refresh token on every refresh and invalidates the old
 * one -- only the most recently issued token is accepted. A store that treats the
 * refresh token as write-once works perfectly against eBay and then locks the
 * Mercado Libre account out after its first refresh, with an error that points at
 * authorization rather than at storage.
 *
 * So this object is immutable and {@see refreshed()} always returns a new instance
 * carrying whatever refresh token came back. See survos/mono#52.
 */
final readonly class MarketplaceToken
{
    /** @param list<string> $scopes */
    public function __construct(
        public string $accessToken,
        public \DateTimeImmutable $accessTokenExpiresAt,
        public ?string $refreshToken = null,
        public ?\DateTimeImmutable $refreshTokenExpiresAt = null,
        public array $scopes = [],
    ) {
    }

    /**
     * @param int          $expiresIn seconds, as both providers report it
     * @param list<string> $scopes
     */
    public static function fromExpiresIn(
        string $accessToken,
        int $expiresIn,
        ?string $refreshToken = null,
        ?int $refreshExpiresIn = null,
        array $scopes = [],
        ?\DateTimeImmutable $now = null,
    ): self {
        $now ??= new \DateTimeImmutable();

        return new self(
            accessToken: $accessToken,
            accessTokenExpiresAt: $now->modify(sprintf('+%d seconds', $expiresIn)),
            refreshToken: $refreshToken,
            refreshTokenExpiresAt: $refreshExpiresIn !== null
                ? $now->modify(sprintf('+%d seconds', $refreshExpiresIn))
                : null,
            scopes: $scopes,
        );
    }

    /**
     * Treat the token as expired slightly early, so a call started just under the
     * wire does not arrive just over it.
     */
    public function isExpired(?\DateTimeImmutable $now = null, int $leewaySeconds = 60): bool
    {
        $now ??= new \DateTimeImmutable();

        return $this->accessTokenExpiresAt <= $now->modify(sprintf('+%d seconds', $leewaySeconds));
    }

    public function canRefresh(?\DateTimeImmutable $now = null): bool
    {
        if ($this->refreshToken === null) {
            return false;
        }
        if ($this->refreshTokenExpiresAt === null) {
            return true;
        }

        return $this->refreshTokenExpiresAt > ($now ?? new \DateTimeImmutable());
    }

    /**
     * Build the successor to this token after a refresh.
     *
     * When the provider returns no new refresh token (eBay's usual behaviour), the
     * current one carries forward. When it does return one (Mercado Libre, always),
     * the new one replaces it and the old one is already dead.
     */
    public function refreshed(
        string $accessToken,
        int $expiresIn,
        ?string $newRefreshToken = null,
        ?int $refreshExpiresIn = null,
        ?\DateTimeImmutable $now = null,
    ): self {
        $now ??= new \DateTimeImmutable();

        return new self(
            accessToken: $accessToken,
            accessTokenExpiresAt: $now->modify(sprintf('+%d seconds', $expiresIn)),
            refreshToken: $newRefreshToken ?? $this->refreshToken,
            refreshTokenExpiresAt: $refreshExpiresIn !== null
                ? $now->modify(sprintf('+%d seconds', $refreshExpiresIn))
                : ($newRefreshToken !== null ? null : $this->refreshTokenExpiresAt),
            scopes: $this->scopes,
        );
    }
}
