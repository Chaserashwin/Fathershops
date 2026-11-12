<?php

namespace App\Data;

use App\Support\Text;

/**
 * The slim product shape used in category listings.
 *
 * Upstream listing entries carry ~8 KB of description HTML each; none of it
 * is needed for a product card, so it is dropped here.
 */
final readonly class ProductSummary
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $image,
        public ?string $price,
        public int $stock,
        public float $rating,
        public int $reviews,
        public ?string $sku,
    ) {}

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromUpstream(array $raw, string $languageId): ?self
    {
        $id = (int) ($raw['product_id'] ?? 0);
        $name = self::localisedName($raw['descriptions'] ?? null, $languageId);

        if ($id <= 0 || $name === '') {
            return null;
        }

        return new self(
            id: $id,
            name: $name,
            image: self::url($raw['image'] ?? null),
            price: Text::plain($raw['cost'] ?? '') ?: null,
            stock: max(0, (int) ($raw['quantity'] ?? 0)),
            rating: round((float) ($raw['rating'] ?? 0), 1),
            reviews: max(0, (int) ($raw['reviews'] ?? 0)),
            sku: Text::plain($raw['sku'] ?? '') ?: null,
        );
    }

    /**
     * Pick the name for the requested language, falling back to any
     * language that has one.
     */
    public static function localisedName(mixed $descriptions, string $languageId): string
    {
        if (! is_array($descriptions)) {
            return '';
        }

        $preferred = Text::plain($descriptions[$languageId]['name'] ?? '');

        if ($preferred !== '') {
            return $preferred;
        }

        foreach ($descriptions as $description) {
            $name = Text::plain($description['name'] ?? '');

            if ($name !== '') {
                return $name;
            }
        }

        return '';
    }

    public static function url(mixed $value): ?string
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_URL) ? $value : null;
    }
}
