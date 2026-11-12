<?php

use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CollectionController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserPasswordController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\Customer\AccountController;
use App\Http\Controllers\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\PublicOrderController;
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

// Confirmación pública del pedido para el retorno de Wompi. El token del path
// autoriza la lectura (Wompi agrega ?id=... al volver) y no exige sesión.
Route::get('/pedido/{order:code}/{token}', [PublicOrderController::class, 'show'])
    ->name('orders.public.show');

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

    // Recuperación de contraseña — enlace por correo, token de un solo uso.
    Route::get('/recuperar-contrasena', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/recuperar-contrasena', [PasswordResetController::class, 'sendLink'])->name('password.email')->middleware('throttle:6,1');
    Route::get('/restablecer-contrasena/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/restablecer-contrasena', [PasswordResetController::class, 'reset'])->name('password.store')->middleware('throttle:6,1');
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

// Panel del cliente — solo el rol cliente actualiza sus datos y su contraseña.
// Los pedidos siguen en /mis-pedidos (auth, cada quien ve los suyos).
Route::middleware(['auth', 'role:cliente'])->prefix('mi-cuenta')->name('customer.account.')->group(function () {
    Route::get('/', [AccountController::class, 'edit'])->name('edit');
    Route::put('/datos', [AccountController::class, 'update'])->name('update');
    Route::get('/contrasena', [AccountController::class, 'editPassword'])->name('password.edit');
    // Prefijo propio en cada throttle: Laravel usa solo el ID del usuario como
    // clave para autenticados, así que sin prefijo las tres rutas compartirían
    // el mismo contador y agotar la verificación bloquearía el reenvío.
    Route::put('/contrasena', [AccountController::class, 'updatePassword'])
        ->middleware('throttle:6,1,password-change-start')
        ->name('password.update');
    Route::get('/contrasena/verificar', [AccountController::class, 'showPasswordVerification'])
        ->name('password.verify');
    Route::post('/contrasena/verificar', [AccountController::class, 'verifyPassword'])
        ->middleware('throttle:10,1,password-change-verify')
        ->name('password.verify.submit');
    Route::post('/contrasena/verificar/reenviar', [AccountController::class, 'resendPasswordCode'])
        ->middleware('throttle:6,1,password-change-resend')
        ->name('password.verify.resend');
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

    // Marcas — exclusivo del admin. El empleado puede asignarlas al editar
    // productos, pero no crear, desactivar ni eliminar marcas.
    Route::resource('brands', BrandController::class)->except(['show']);

    Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
    Route::get('users/{user}/password', [UserPasswordController::class, 'edit'])->name('users.password.edit');
    Route::put('users/{user}/password', [UserPasswordController::class, 'update'])->name('users.password.update');
    Route::resource('users', UserController::class)->except(['show', 'destroy']);

    // Clientes — solo admin: datos de contacto, estado y conteo de pedidos.
    // La contraseña la gestiona el propio cliente en /mi-cuenta.
    Route::get('clientes', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('clientes/{user}', [CustomerController::class, 'show'])->name('customers.show');
    Route::get('clientes/{user}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
    Route::put('clientes/{user}', [CustomerController::class, 'update'])->name('customers.update');
    Route::patch('clientes/{user}/toggle-active', [CustomerController::class, 'toggleActive'])->name('customers.toggle-active');
});

// Alias en español para compatibilidad blade (opcional)
Route::middleware(['auth', 'role:admin,empleado'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('productos', ProductController::class)->except(['show'])->parameters(['productos' => 'product']);
});
