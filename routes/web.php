<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductTagController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('products.index');
})->name('home');

Route::post('products/{product}/tags', [ProductTagController::class, 'store'])
    ->name('products.tags.store');

Route::delete('products/{product}/tags/{tag}', [ProductTagController::class, 'destroy'])
    ->name('products.tags.destroy');

Route::patch('products/{product}/quantity', [ProductController::class, 'adjustQuantity'])
    ->name('products.quantity');

Route::resource('products', ProductController::class)
    ->names('products');
