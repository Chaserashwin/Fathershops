<?php

use Illuminate\Support\Facades\Route;

// This application is an API only; the root simply points to the endpoints.
Route::get('/', fn () => response()->json([
    'name' => config('app.name'),
    'endpoints' => [
        'categories' => url('/api/v1/categories'),
        'category_products' => url('/api/v1/categories/{category}/products'),
        'product' => url('/api/v1/products/{product}'),
    ],
]));
