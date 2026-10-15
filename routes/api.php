<?php

use App\Http\Controllers\Api\V1;
use Illuminate\Support\Facades\Route;

/*
| REST API v1 — /api/v1/...  (token: Manager → Settings → REST API)
| Auth header: Authorization: Bearer <token>; plan feature "api" required.
*/
Route::prefix('v1')->middleware('throttle:120,1')->group(function () {
    Route::get('/store', [V1\StoreApiController::class, 'show'])->middleware('api.token:store.read');
    Route::get('/categories', [V1\StoreApiController::class, 'categories'])->middleware('api.token:store.read');

    Route::get('/products', [V1\ProductApiController::class, 'index'])->middleware('api.token:products.read');
    Route::get('/products/{id}', [V1\ProductApiController::class, 'show'])->whereNumber('id')->middleware('api.token:products.read');
    Route::post('/products', [V1\ProductApiController::class, 'store'])->middleware('api.token:products.write');
    Route::put('/products/{id}', [V1\ProductApiController::class, 'update'])->whereNumber('id')->middleware('api.token:products.write');
    Route::post('/products/{id}/stock', [V1\ProductApiController::class, 'stock'])->whereNumber('id')->middleware('api.token:products.write');

    Route::get('/orders', [V1\OrderApiController::class, 'index'])->middleware('api.token:orders.read');
    Route::get('/orders/{number}', [V1\OrderApiController::class, 'show'])->middleware('api.token:orders.read');
    Route::patch('/orders/{number}/status', [V1\OrderApiController::class, 'status'])->middleware('api.token:orders.write');

    Route::get('/customers', [V1\CustomerApiController::class, 'index'])->middleware('api.token:customers.read');
    Route::get('/customers/{id}', [V1\CustomerApiController::class, 'show'])->whereNumber('id')->middleware('api.token:customers.read');
    Route::post('/customers', [V1\CustomerApiController::class, 'store'])->middleware('api.token:customers.write');

    Route::fallback(fn () => response()->json(['message' => 'Endpoint not found.'], 404));
});
