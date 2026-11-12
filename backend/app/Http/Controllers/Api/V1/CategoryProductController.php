<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryProductsRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductSummaryResource;
use App\Services\FatherStock\CatalogService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

class CategoryProductController extends Controller
{
    /**
     * GET /api/v1/categories/{category}/products
     *
     * Paginated products of one category. Responds with 404 when the
     * category does not exist.
     */
    public function index(CategoryProductsRequest $request, CatalogService $catalog, int $category): AnonymousResourceCollection
    {
        $products = $catalog->categoryProducts($category, $request->language());

        $page = $request->page();
        $perPage = $request->perPage();

        $paginator = new LengthAwarePaginator(
            items: array_slice($products, ($page - 1) * $perPage, $perPage),
            total: count($products),
            perPage: $perPage,
            currentPage: $page,
            options: ['path' => $request->url(), 'query' => $request->query()],
        );

        $details = $catalog->findCategory($category);

        return ProductSummaryResource::collection($paginator)->additional([
            'category' => $details
                ? new CategoryResource($details)
                : ['id' => $category, 'name' => null, 'image' => null, 'parent_id' => null],
        ]);
    }
}
