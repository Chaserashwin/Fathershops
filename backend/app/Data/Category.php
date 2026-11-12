<?php

namespace App\Data;

use App\Support\Text;

final readonly class Category
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $image,
        public ?int $parentId,
    ) {}

    /**
     * Build a category from an upstream "hot-category" entry, or return null
     * when the entry is unusable (missing id or name).
     *
     * @param  array<string, mixed>  $raw
     */
    public static function fromUpstream(array $raw): ?self
    {
        $id = (int) ($raw['category_id'] ?? 0);
        $name = Text::plain($raw['name'] ?? '');

        if ($id <= 0 || $name === '') {
            return null;
        }

        $parentId = (int) ($raw['parent_id'] ?? 0);

        return new self(
            id: $id,
            name: $name,
            image: self::url($raw['image'] ?? null),
            parentId: $parentId > 0 ? $parentId : null,
        );
    }

    private static function url(mixed $value): ?string
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_URL) ? $value : null;
    }
}
