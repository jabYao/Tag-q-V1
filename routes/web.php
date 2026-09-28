<?php

use App\Http\Controllers\Admin\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Autenticación — monolito Blade + Livewire (AGENTS.md §1)
Route::middleware('guest')->group(function () {
    Route::get('/login', fn () => view('auth.login'))->name('login');
});

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

// Panel admin — solo admin y empleado (cliente e invitado quedan fuera)
Route::middleware(['auth', 'role:admin,empleado'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('products', ProductController::class)->except(['show'])->parameters(['products' => 'product']);
});

// Alias en español para compatibilidad blade (opcional)
Route::middleware(['auth', 'role:admin,empleado'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('productos', ProductController::class)->except(['show'])->parameters(['productos' => 'product']);
});
