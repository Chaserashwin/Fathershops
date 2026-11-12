<?php

namespace App\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;

/**
 * The requested catalog resource does not exist (HTTP 404).
 *
 * Expected during normal use, so it is not written to the log.
 */
class ResourceNotFoundException extends ApiException implements ShouldntReport
{
    public function __construct(string $message, private readonly string $errorCode = 'not_found')
    {
        parent::__construct($message);
    }

    public static function categories(): self
    {
        return new self('No categories are available right now.', 'categories_not_found');
    }

    public static function category(int $categoryId): self
    {
        return new self("Category {$categoryId} was not found.", 'category_not_found');
    }

    public static function product(int $productId): self
    {
        return new self("Product {$productId} was not found.", 'product_not_found');
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }
}
