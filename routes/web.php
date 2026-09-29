<?php

use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\CatalogController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Catálogo público — accesible sin autenticación (monolito Blade, AGENTS.md §1)
Route::get('/catalogo', [CatalogController::class, 'index'])->name('catalog.index');

// Detalle público de producto por slug (binding implícito: /catalogo/{slug})
Route::get('/catalogo/{product:slug}', [CatalogController::class, 'show'])->name('catalog.show');

// Carrito público — invitado en sesión, cliente autenticado en carts/cart_items.
Route::get('/carrito', fn () => view('cart.index'))->name('cart.index');

// Checkout público MVP contraentrega — accesible a invitado y cliente autenticado.
Route::get('/checkout', fn () => view('checkout.index'))->name('checkout.index');

// Autenticación — monolito Blade + Livewire (AGENTS.md §1)
Route::middleware('guest')->group(function () {
    Route::get('/login', fn () => view('auth.login'))->name('login');

    // Registro público de clientes con verificación por código de correo.
    Route::get('/register', [RegistrationController::class, 'create'])->name('register');
    Route::post('/register', [RegistrationController::class, 'store'])->name('register.store');
    Route::get('/register/verify', [RegistrationController::class, 'showVerify'])->name('register.verify');
    Route::post('/register/verify', [RegistrationController::class, 'verify'])->name('register.verify.submit');
    Route::post('/register/resend', [RegistrationController::class, 'resend'])->name('register.resend');
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

// Gestión de usuarios — solo admin (empleado, cliente e invitado quedan fuera)
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
    Route::resource('users', UserController::class)->except(['show', 'destroy']);
});

// Alias en español para compatibilidad blade (opcional)
Route::middleware(['auth', 'role:admin,empleado'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('productos', ProductController::class)->except(['show'])->parameters(['productos' => 'product']);
});
