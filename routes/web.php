<?php

use App\Http\Controllers\Admin\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Stub para auth middleware — sin Breeze aún, evita RouteNotFoundException en tests y redirect
Route::get('/login', fn () => response('Login requerido', 401))->name('login');

// Panel admin — monolito Blade + Livewire, sin API separada (AGENTS.md §1)
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('products', ProductController::class)->except(['show'])->parameters(['products' => 'product']);
});

// Alias en español para compatibilidad blade (opcional)
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('productos', ProductController::class)->except(['show'])->parameters(['productos' => 'product']);
});
