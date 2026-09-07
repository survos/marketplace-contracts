<?php

declare(strict_types=1);

namespace Survos\MarketplaceContracts\Model;

/**
 * One reason a draft is not publishable.
 *
 * Collected rather than thrown, so a caller can fix every problem in one pass --
 * and so the whole set can be handed back to a language model as corrective
 * context instead of it discovering them one rejected publish at a time.
 */
final readonly class ListingViolation implements \Stringable
{
    public function __construct(
        public ViolationCode $code,
        public string $field,
        public string $message,
    ) {
    }

    public static function missingRequiredAttribute(string $name): self
    {
        return new self(
            ViolationCode::MissingRequiredAttribute,
            'attributes.' . $name,
            sprintf('Required attribute "%s" is missing.', $name),
        );
    }

    /** @param list<string> $allowed */
    public static function valueNotAllowed(string $name, string $value, array $allowed): self
    {
        $shown = array_slice($allowed, 0, 8);
        $suffix = count($allowed) > count($shown) ? sprintf(' (and %d more)', count($allowed) - count($shown)) : '';

        return new self(
            ViolationCode::ValueNotAllowed,
            'attributes.' . $name,
            sprintf('"%s" is not an accepted value for "%s". Allowed: %s%s.', $value, $name, implode(', ', $shown), $suffix),
        );
    }

    public function __toString(): string
    {
        return sprintf('[%s] %s: %s', $this->code->value, $this->field, $this->message);
    }
}
