<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Services\FatherStock\CatalogService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    /**
     * GET /api/v1/categories
     *
     * Featured categories with their images. Responds with 404 when the
     * upstream request fails or returns no categories.
     */
    public function index(CatalogService $catalog): AnonymousResourceCollection
    {
        return CategoryResource::collection($catalog->categories());
    }
}
