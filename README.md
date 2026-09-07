# survos/marketplace-contracts

Provider-neutral contracts and models for listing items on online marketplaces —
currently eBay and Mercado Libre.

Framework-agnostic and dependency-free: no Symfony kernel, no container, no HTTP
client, no `require` beyond PHP itself. The same models are used from a Symfony
application (`survos/marketplace-bundle`), a WordPress plugin, or a plain script.

This package is to marketplaces what [`survos/record-store`](https://github.com/survos/record-store)
is to record backends: the shared vocabulary, with the drivers shipped separately.

```bash
composer require survos/marketplace-contracts
```

## The shape

| | |
|---|---|
| `Model\ListingDraft` | everything needed to publish one listing, provider-neutral and immutable |
| `Model\AttributeSchema` | what a category demands — and `toJsonSchema()` for structured model output |
| `Model\Money` | exact amounts in minor units; never a float |
| `Model\ShippingSpec` | flat shipping: first item + each additional |
| `Model\MarketplaceToken` | an OAuth pair, with rotation handled correctly |
| `Contract\MarketplaceAdapterInterface` | what a driver implements |
| `Registry\MarketplaceRegistry` | resolves a `ConnectionConfiguration` to an adapter |

## Why the interface is this narrow

`publish()` barely generalizes. On eBay it is an inventory item, then an offer, then
a publish — against business policies and a merchant location that must already
exist. On Mercado Libre it is a single `POST /items`. The two adapters share no code
and there is nothing to be gained from pretending otherwise.

What *does* generalize is the step before it:

```
title → suggestCategories() → attributeSchema() → toJsonSchema() → generate → validate → publish
```

Asking the provider what a category requires, and feeding that back into generation,
is identical on both marketplaces and is what decides whether a listing publishes or
bounces. That loop is the reason this package exists.

```php
use Survos\MarketplaceContracts\Model\{ListingDraft, Money};

$draft = new ListingDraft(
    sku:         'PC-ANIMALS-001',
    title:       'Lot of 5 Vintage Animal Postcards',
    description: 'Five themed postcards, 1930s-1950s.',
    price:       Money::fromDecimal('4.00', 'USD'),
);

$suggestion = $adapter->suggestCategories($draft->title)[0];
$schema     = $adapter->attributeSchema($suggestion->categoryId);

// Hand the category's own requirements to the model, so required attributes are
// satisfied at generation time rather than discovered in a rejection.
$attributes = $someModel->generate($draft, $schema->toJsonSchema());

$draft = $draft->withCategoryId($suggestion->categoryId)->withAttributes($attributes);

$violations = [
    ...$draft->violations($adapter->limits()),
    ...$schema->violationsFor($draft),
];

if ($violations === []) {
    $listing = $adapter->publish($draft);
}
```

## Three things this package is opinionated about

**Money is never a float.** `Money` holds minor units and an explicit currency
exponent. Prices get compared, summed and echoed back to sellers; binary floating
point loses cents, and the loss shows up in a live listing.

**Shipping is linear, not banded.** Neither provider can express *"1–3 units $0.90,
4–10 units $1.40"* on a listing — every automatic mechanism they offer is a first-item
cost plus a flat per-additional delta. Modelling bands here would invent a feature
that does not exist and quietly mis-price the result. Pack size belongs in the SKU.

**Refresh tokens rotate.** `MarketplaceToken` is immutable and `refreshed()` always
returns a new instance carrying whatever refresh token came back. Mercado Libre's
refresh tokens are single-use — a new one is issued on every refresh and only the
newest is accepted. A store that treats the refresh token as write-once passes every
eBay test and then locks the Mercado Libre account out permanently on its first
refresh, with an error that points at authorization rather than at storage.
`TokenStoreInterface::save()` documents this as an implementation requirement.

## Tests

```bash
composer install
vendor/bin/phpunit
vendor/bin/phpstan analyse
```
