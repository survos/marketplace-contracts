<?php

declare(strict_types=1);

namespace Survos\MarketplaceContracts\Tests;

use PHPUnit\Framework\TestCase;
use Survos\MarketplaceContracts\Model\MarketplaceToken;

final class MarketplaceTokenTest extends TestCase
{
    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now = new \DateTimeImmutable('2026-09-07 12:00:00');
    }

    public function testMercadoLibreRotationReplacesTheRefreshToken(): void
    {
        // ML: 6h access, and a NEW single-use refresh token on every refresh.
        $token = MarketplaceToken::fromExpiresIn('access-1', 21600, 'refresh-1', now: $this->now);

        $rotated = $token->refreshed('access-2', 21600, 'refresh-2', now: $this->now);

        self::assertSame('refresh-2', $rotated->refreshToken);
        self::assertSame('refresh-1', $token->refreshToken, 'the original must be untouched');
    }

    public function testEbayStyleRefreshCarriesTheExistingRefreshTokenForward(): void
    {
        // eBay returns no new refresh token; the long-lived one stays valid.
        $token = MarketplaceToken::fromExpiresIn('access-1', 7200, 'refresh-1', now: $this->now);

        $refreshed = $token->refreshed('access-2', 7200, now: $this->now);

        self::assertSame('refresh-1', $refreshed->refreshToken);
        self::assertSame('access-2', $refreshed->accessToken);
    }

    public function testExpiryUsesLeewaySoInFlightCallsDoNotLandExpired(): void
    {
        $token = MarketplaceToken::fromExpiresIn('a', 30, now: $this->now);

        self::assertTrue($token->isExpired($this->now), '30s of life is inside the 60s leeway');
        self::assertFalse($token->isExpired($this->now, leewaySeconds: 0));
    }

    public function testCannotRefreshWithoutARefreshToken(): void
    {
        $token = MarketplaceToken::fromExpiresIn('a', 7200, now: $this->now);

        self::assertFalse($token->canRefresh($this->now));
    }

    public function testCannotRefreshOnceTheRefreshTokenHasExpired(): void
    {
        $token = MarketplaceToken::fromExpiresIn('a', 7200, 'r', refreshExpiresIn: 100, now: $this->now);

        self::assertTrue($token->canRefresh($this->now));
        self::assertFalse($token->canRefresh($this->now->modify('+200 seconds')));
    }
}
