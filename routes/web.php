<?php

use App\Http\Controllers\Admin\CollectionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\StorefrontHomeController;
use App\Http\Controllers\WompiWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Home público: categorías existentes y hasta cuatro productos activos recientes.
Route::get('/', StorefrontHomeController::class)->name('home');

// Catálogo público — accesible sin autenticación (monolito Blade, AGENTS.md §1)
Route::get('/catalogo', [CatalogController::class, 'index'])->name('catalog.index');

// Detalle público de producto por slug (binding implícito: /catalogo/{slug})
Route::get('/catalogo/{product:slug}', [CatalogController::class, 'show'])->name('catalog.show');

// Páginas legales públicas. El contenido todavía no está aprobado: cada ruta
// sirve la misma vista Blade provisional (`Route::view`, sin controlador ni
// paquete nuevos) que conserva su título y avisa que el texto está pendiente.
// Son destinos reales y navegables desde el footer del storefront.
Route::view('/politica-de-privacidad', 'legal.placeholder', ['title' => 'Política de privacidad', 'key' => 'privacy'])->name('legal.privacy');
Route::view('/terminos-y-condiciones', 'legal.placeholder', ['title' => 'Términos y condiciones', 'key' => 'terms'])->name('legal.terms');
Route::view('/politica-de-cookies', 'legal.placeholder', ['title' => 'Política de cookies', 'key' => 'cookies'])->name('legal.cookies');
Route::view('/cambios-y-devoluciones', 'legal.placeholder', ['title' => 'Cambios y devoluciones', 'key' => 'returns'])->name('legal.returns');
Route::view('/envios-y-entregas', 'legal.placeholder', ['title' => 'Envíos y entregas', 'key' => 'shipping'])->name('legal.shipping');
Route::view('/medios-de-pago', 'legal.placeholder', ['title' => 'Medios de pago', 'key' => 'payments'])->name('legal.payments');
Route::view('/contacto', 'legal.placeholder', ['title' => 'Contacto', 'key' => 'contact'])->name('legal.contact');

// Carrito público — invitado en sesión, cliente autenticado en carts/cart_items.
Route::get('/carrito', fn () => view('cart.index'))->name('cart.index');

// Checkout público MVP contraentrega — accesible a invitado y cliente autenticado.
Route::get('/checkout', fn () => view('checkout.index'))->name('checkout.index');

// Webhook de Wompi (transaction.updated): lo invoca Wompi, no el navegador.
// Se excluye de CSRF en bootstrap/app.php; la autenticidad la da la firma del evento.
Route::post('/webhooks/wompi', WompiWebhookController::class)->name('webhooks.wompi');

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

// Historial de pedidos del cliente autenticado — parte 3 de pedidos (cara pública).
// Cada usuario ve únicamente auth()->user()->orders(); pedido ajeno o inexistente = 404.
Route::middleware('auth')->prefix('mis-pedidos')->name('customer.orders.')->group(function () {
    Route::get('/', [CustomerOrderController::class, 'index'])->name('index');
    Route::get('/{order}', [CustomerOrderController::class, 'show'])->name('show');
});

// Panel admin — solo admin y empleado (cliente e invitado quedan fuera)
Route::middleware(['auth', 'role:admin,empleado'])->prefix('admin')->name('admin.')->group(function () {
    // Hoja de operación diaria: el nuevo inicio del panel.
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('products', ProductController::class)->except(['show'])->parameters(['products' => 'product']);

    // Pedidos — solo admin y empleado: listado, detalle, avance del flujo
    // operativo y salidas de cancelación/reembolso (parte 4).
    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
    Route::patch('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('orders/{order}/refund', [OrderController::class, 'refund'])->name('orders.refund');
});

// Gestión de usuarios — solo admin (empleado, cliente e invitado quedan fuera)
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Colecciones curadas — exclusivo del admin (el empleado no publica colecciones).
    Route::resource('collections', CollectionController::class)->except(['show']);

    Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
    Route::resource('users', UserController::class)->except(['show', 'destroy']);
});

// Alias en español para compatibilidad blade (opcional)
Route::middleware(['auth', 'role:admin,empleado'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('productos', ProductController::class)->except(['show'])->parameters(['productos' => 'product']);
});
