<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductTagController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('products.index');
})->name('home');

Route::put('products/{product}/tags', [ProductTagController::class, 'update'])
    ->name('products.tags.update');

Route::get('products/{product}/tags/suggest', [ProductTagController::class, 'suggest'])
    ->name('products.tags.suggest');

Route::patch('products/{product}/quantity', [ProductController::class, 'adjustQuantity'])
    ->name('products.quantity');

Route::resource('products', ProductController::class)
    ->names('products');
