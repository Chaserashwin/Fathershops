<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CatalogRequest;
use App\Http\Resources\ProductResource;
use App\Services\FatherStock\CatalogService;

class ProductController extends Controller
{
    /**
     * GET /api/v1/products/{product}
     *
     * Full details of one product. Responds with 404 when the product does
     * not exist.
     */
    public function show(CatalogRequest $request, CatalogService $catalog, int $product): ProductResource
    {
        return new ProductResource($catalog->product($product, $request->language()));
    }
}
