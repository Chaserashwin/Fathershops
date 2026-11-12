<?php

use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CategoryProductController;
use App\Http\Controllers\Api\V1\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Catalog API (v1)
|--------------------------------------------------------------------------
|
| Read-only endpoints consumed by the Next.js storefront. Successful
| responses carry Cache-Control + ETag headers so browsers and proxies can
| revalidate with a cheap 304 instead of downloading the payload again.
|
*/

Route::prefix('v1')
    ->name('api.v1.')
    ->middleware(['throttle:catalog', 'cache.headers:public;max_age=300;etag'])
    ->group(function (): void {
        Route::get('categories', [CategoryController::class, 'index'])
            ->name('categories.index');

        Route::get('categories/{category}/products', [CategoryProductController::class, 'index'])
            ->whereNumber('category')
            ->name('categories.products.index');

        Route::get('products/{product}', [ProductController::class, 'show'])
            ->whereNumber('product')
            ->name('products.show');
    });
