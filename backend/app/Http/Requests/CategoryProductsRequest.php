<?php

namespace App\Http\Requests;

class CategoryProductsRequest extends CatalogRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return parent::rules() + [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.config('fatherstock.pagination.max_per_page')],
        ];
    }

    public function page(): int
    {
        return (int) $this->validated('page', 1);
    }

    public function perPage(): int
    {
        return (int) $this->validated('per_page', config('fatherstock.pagination.per_page'));
    }
}
