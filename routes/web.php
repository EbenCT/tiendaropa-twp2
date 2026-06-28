<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BuscadorController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Cliente\CarritoController;
use App\Http\Controllers\Cliente\FavoritoController;
use App\Http\Controllers\Cliente\PedidoController;
use App\Http\Controllers\Cliente\PagoController;
use App\Http\Controllers\Cliente\MetodoPagoController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\Admin\ProductoAdminController;
use App\Http\Controllers\Admin\InventarioController;
use App\Http\Controllers\Admin\PedidoAdminController;
use App\Http\Controllers\Admin\UsuarioAdminController;
use App\Http\Controllers\Admin\MenuAdminController;
use App\Http\Controllers\Admin\EstadisticaController;
use App\Http\Controllers\Admin\ReporteController;
use App\Http\Controllers\Admin\DestacadoController;
use Illuminate\Support\Facades\Route;

// ═══════════════════════════════════════════════════════════════
// RUTAS PÚBLICAS
// ═══════════════════════════════════════════════════════════════

Route::get('/',              [HomeController::class, 'index'])->name('home');
Route::get('/catalogo',      [CatalogoController::class, 'index'])->name('catalogo');
Route::get('/promociones',   [CatalogoController::class, 'index'])->name('promociones');
Route::get('/catalogo/{id}', [CatalogoController::class, 'show'])->name('catalogo.show');
Route::get('/buscar',        [BuscadorController::class, 'buscar'])->name('buscar');

// Webhook de Stripe — sin sesión/CSRF, protegido por verificación de firma en el controlador
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])->name('stripe.webhook');

// ═══════════════════════════════════════════════════════════════
// AUTH
// ═══════════════════════════════════════════════════════════════

Route::middleware('guest')->group(function () {
    Route::get('/login',    [LoginController::class, 'create'])->name('login');
    Route::post('/login',   [LoginController::class, 'store']);
    Route::get('/registro', [RegisterController::class, 'create'])->name('registro');
    Route::post('/registro',[RegisterController::class, 'store']);
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// ═══════════════════════════════════════════════════════════════
// CLIENTE (nivel 1+) — auth requerido
// ═══════════════════════════════════════════════════════════════

Route::middleware(['auth'])->group(function () {

    // Carrito
    Route::prefix('carrito')->name('carrito.')->group(function () {
        Route::get('/',              [CarritoController::class, 'index'])->name('index');
        Route::post('/agregar',      [CarritoController::class, 'agregar'])->name('agregar');
        Route::patch('/{id}',        [CarritoController::class, 'actualizar'])->name('actualizar');
        Route::delete('/{id}',       [CarritoController::class, 'eliminar'])->name('eliminar');
    });

    // Favoritos
    Route::prefix('favoritos')->name('favoritos.')->group(function () {
        Route::get('/',              [FavoritoController::class, 'index'])->name('index');
        Route::post('/toggle/{id}',  [FavoritoController::class, 'toggle'])->name('toggle');
    });

    // Pedidos (cliente)
    Route::prefix('pedidos')->name('pedidos.')->group(function () {
        Route::get('/crear',         [PedidoController::class, 'create'])->name('create');
        Route::post('/',             [PedidoController::class, 'store'])->name('store');
        Route::get('/historial',     [PedidoController::class, 'historial'])->name('historial');
        Route::get('/pago/exito',     [PagoController::class, 'exito'])->name('pago.exito');
        Route::get('/pago/cancelado', [PagoController::class, 'cancelado'])->name('pago.cancelado');
        Route::get('/{id}',          [PedidoController::class, 'show'])->name('show');
        Route::get('/{id}/pagar',         [PagoController::class, 'mostrarPago'])->name('pagar');
        Route::post('/{id}/pagar/unico',  [PagoController::class, 'iniciarPagoUnico'])->name('pagar.unico');
        Route::post('/{id}/pagar/cuotas', [PagoController::class, 'iniciarPagoCuotas'])->name('pagar.cuotas');
    });

    // Métodos de pago guardados (Stripe)
    Route::prefix('metodos-pago')->name('metodos-pago.')->group(function () {
        Route::get('/',                 [MetodoPagoController::class, 'index'])->name('index');
        Route::post('/setup-intent',    [MetodoPagoController::class, 'crearSetupIntent'])->name('setup-intent');
        Route::patch('/{id}/principal', [MetodoPagoController::class, 'marcarPrincipal'])->name('principal');
        Route::delete('/{id}',          [MetodoPagoController::class, 'eliminar'])->name('eliminar');
    });
});

// ═══════════════════════════════════════════════════════════════
// VENDEDOR+ (nivel 2+)
// ═══════════════════════════════════════════════════════════════

Route::middleware(['auth', 'role:vendedor'])->prefix('admin')->name('admin.')->group(function () {

    // Productos
    Route::resource('productos', ProductoAdminController::class)
        ->names('productos');

    // Inventario
    Route::prefix('inventario')->name('inventario.')->group(function () {
        Route::get('/',      [InventarioController::class, 'index'])->name('index');
        Route::get('/crear', [InventarioController::class, 'create'])->name('create');
        Route::post('/',     [InventarioController::class, 'store'])->name('store');
    });

    // Pedidos admin
    Route::prefix('pedidos')->name('pedidos.')->group(function () {
        Route::get('/',           [PedidoAdminController::class, 'index'])->name('index');
        Route::get('/{id}',       [PedidoAdminController::class, 'show'])->name('show');
        Route::patch('/{id}',     [PedidoAdminController::class, 'cambiarEstado'])->name('estado');
    });

    // Destacados (home)
    Route::post('destacados/toggle/{id}', [DestacadoController::class, 'toggle'])->name('destacados.toggle');
});

// ═══════════════════════════════════════════════════════════════
// PROPIETARIO+ (nivel 3+)
// ═══════════════════════════════════════════════════════════════

Route::middleware(['auth', 'role:propietario'])->prefix('admin')->name('admin.')->group(function () {

    // Usuarios
    Route::resource('usuarios', UsuarioAdminController::class)
        ->names('usuarios');

    // Reportes
    Route::get('reportes', [ReporteController::class, 'index'])->name('reportes');
    Route::get('estadisticas', [EstadisticaController::class, 'index'])->name('estadisticas');
});

// ═══════════════════════════════════════════════════════════════
// ADMIN (nivel 4)
// ═══════════════════════════════════════════════════════════════

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {

    // Menú dinámico
    Route::resource('menu', MenuAdminController::class)
        ->names('menu');
});
