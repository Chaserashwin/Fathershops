<?php

namespace App\Http\Resources;

use App\Data\ProductDetail;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read ProductDetail $resource
 */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $product = $this->resource;

        return [
            'id' => $product->id,
            'name' => $product->name,
            'description_html' => $product->descriptionHtml,
            'image' => $product->image,
            'images' => $product->images,
            'price' => $product->price,
            'currency' => $product->currency,
            'stock' => $product->stock,
            'in_stock' => $product->inStock,
            'sku' => $product->sku,
            'model' => $product->model,
            'type' => $product->type,
            'source' => $product->source,
            'rating' => $product->rating,
            'reviews' => $product->reviews,
            'options' => $product->options,
            'dimensions' => $product->dimensions,
            'seo' => [
                'title' => $product->metaTitle,
                'description' => $product->metaDescription,
            ],
        ];
    }
}
