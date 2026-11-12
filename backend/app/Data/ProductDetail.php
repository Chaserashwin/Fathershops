<?php

namespace App\Data;

use App\Support\Text;

final readonly class ProductDetail
{
    /**
     * @param  list<string>  $images
     * @param  list<array{id: int, name: string, type: string, required: bool, values: list<array{id: int, name: string, image: ?string, stock: int, price_modifier: ?string}>}>  $options
     * @param  array{weight: ?string, length: ?string, width: ?string, height: ?string}  $dimensions
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $descriptionHtml,
        public ?string $metaTitle,
        public ?string $metaDescription,
        public ?string $image,
        public array $images,
        public ?string $price,
        public ?string $currency,
        public int $stock,
        public bool $inStock,
        public ?string $sku,
        public ?string $model,
        public ?string $type,
        public ?string $source,
        public float $rating,
        public int $reviews,
        public array $options,
        public array $dimensions,
    ) {}

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromUpstream(array $raw, string $languageId): ?self
    {
        $id = (int) ($raw['product_id'] ?? 0);
        $descriptions = is_array($raw['descriptions'] ?? null) ? $raw['descriptions'] : [];
        $name = ProductSummary::localisedName($descriptions, $languageId);

        if ($id <= 0 || $name === '') {
            return null;
        }

        $text = $descriptions[$languageId] ?? (reset($descriptions) ?: []);
        $image = ProductSummary::url($raw['image'] ?? null);
        $stock = max(0, (int) ($raw['quantity'] ?? 0));

        return new self(
            id: $id,
            name: $name,
            descriptionHtml: Text::richHtml($text['description'] ?? ''),
            metaTitle: Text::plain($text['meta_title'] ?? '') ?: null,
            metaDescription: Text::plain($text['meta_description'] ?? '') ?: null,
            image: $image,
            images: self::gallery($raw['images'] ?? null, $image),
            price: Text::plain($raw['cost'] ?? '') ?: null,
            currency: Text::plain($raw['currency'] ?? '') ?: null,
            stock: $stock,
            inStock: $stock > 0 && (string) ($raw['status_id'] ?? '1') === '1',
            sku: Text::plain($raw['sku'] ?? '') ?: null,
            model: Text::plain($raw['model'] ?? '') ?: null,
            type: Text::plain($raw['type'] ?? '') ?: null,
            source: Text::plain($raw['product_source'] ?? '') ?: null,
            rating: round((float) ($raw['rating'] ?? 0), 1),
            reviews: max(0, (int) ($raw['reviews'] ?? 0)),
            options: self::options($raw['options'] ?? null, $languageId),
            dimensions: self::dimensions($raw),
        );
    }

    /**
     * Unique, valid gallery URLs with the main image first.
     *
     * @return list<string>
     */
    private static function gallery(mixed $images, ?string $main): array
    {
        $urls = $main !== null ? [$main] : [];

        foreach (is_array($images) ? $images : [] as $entry) {
            $url = ProductSummary::url(is_array($entry) ? ($entry['image'] ?? null) : $entry);

            if ($url !== null) {
                $urls[] = $url;
            }
        }

        return array_values(array_unique($urls));
    }

    /**
     * @return list<array{id: int, name: string, type: string, required: bool, values: list<array{id: int, name: string, image: ?string, stock: int, price_modifier: ?string}>}>
     */
    private static function options(mixed $options, string $languageId): array
    {
        $result = [];

        foreach (is_array($options) ? $options : [] as $option) {
            $name = self::localised($option['names'] ?? null, $languageId);
            $values = [];

            foreach (is_array($option['product_option_value'] ?? null) ? $option['product_option_value'] : [] as $value) {
                $valueName = self::localised($value['names'] ?? null, $languageId);

                if ($valueName === '') {
                    continue;
                }

                $price = (float) ($value['price'] ?? 0);

                $values[] = [
                    'id' => (int) ($value['product_option_value_id'] ?? 0),
                    'name' => $valueName,
                    'image' => ProductSummary::url($value['image'] ?? null),
                    'stock' => max(0, (int) ($value['quantity'] ?? 0)),
                    'price_modifier' => $price > 0
                        ? (($value['price_prefix'] ?? '+') === '-' ? '-' : '+').number_format($price, 2)
                        : null,
                ];
            }

            if ($name === '' || $values === []) {
                continue;
            }

            $result[] = [
                'id' => (int) ($option['product_option_id'] ?? 0),
                'name' => $name,
                'type' => Text::plain($option['type'] ?? '') ?: 'select',
                'required' => (string) ($option['required'] ?? '0') === '1',
                'values' => $values,
            ];
        }

        return $result;
    }

    private static function localised(mixed $names, string $languageId): string
    {
        if (! is_array($names)) {
            return '';
        }

        return Text::plain($names[$languageId]['name'] ?? (reset($names)['name'] ?? ''));
    }

    /**
     * Physical measurements, formatted with their unit. Upstream sends
     * "0.00000000" for unknown values; those become null.
     *
     * @param  array<string, mixed>  $raw
     * @return array{weight: ?string, length: ?string, width: ?string, height: ?string}
     */
    private static function dimensions(array $raw): array
    {
        $format = static function (mixed $value, mixed $unit): ?string {
            $number = (float) $value;

            return $number > 0
                ? trim(rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.').' '.Text::plain($unit))
                : null;
        };

        return [
            'weight' => $format($raw['weight'] ?? 0, $raw['weight_class'] ?? ''),
            'length' => $format($raw['length'] ?? 0, $raw['length_class'] ?? ''),
            'width' => $format($raw['width'] ?? 0, $raw['length_class'] ?? ''),
            'height' => $format($raw['height'] ?? 0, $raw['length_class'] ?? ''),
        ];
    }
}
