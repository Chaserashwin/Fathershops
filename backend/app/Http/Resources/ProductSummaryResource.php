<?php

namespace App\Http\Resources;

use App\Data\ProductSummary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read ProductSummary $resource
 */
class ProductSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'image' => $this->resource->image,
            'price' => $this->resource->price,
            'stock' => $this->resource->stock,
            'in_stock' => $this->resource->stock > 0,
            'rating' => $this->resource->rating,
            'reviews' => $this->resource->reviews,
            'sku' => $this->resource->sku,
        ];
    }
}
