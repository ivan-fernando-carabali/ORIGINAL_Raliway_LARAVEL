<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// =========================
// 📦 Controladores principales
// =========================
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\DepBuyController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductDetailController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InventoryDetailController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\EntryController;
use App\Http\Controllers\EntryNoteController;
use App\Http\Controllers\OutputController;
use App\Http\Controllers\ExitDetailController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ProductSupplierController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificacionesController;

/*
|--------------------------------------------------------------------------
| API Routes - Sistema de Inventario Unificado
|--------------------------------------------------------------------------
| Estructura consolidada, limpia y coherente.
| Agrupa rutas públicas, autenticadas y administrativas.
|--------------------------------------------------------------------------
*/

// ✅ Ruta de prueba
Route::get('/ping', fn() => response()->json(['message' => 'API funcionando correctamente 🚀']));

// ==========================
// 🟢 RUTAS PÚBLICAS
// ==========================
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::get('roles-public', [RoleController::class, 'getRolesForRegister']);
Route::post('roles-public', [RoleController::class, 'store']);

// ==========================
// 🟡 RUTAS PROTEGIDAS (Auth)
// ==========================
Route::middleware('auth:sanctum')->group(function () {

    // ======================
    // 🔐 Autenticación
    // ======================
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('profile', [UserController::class, 'profile']);
    Route::get('user', [AuthController::class, 'me']);
    Route::post('user/update-image', [UserController::class, 'updateImage']);
    Route::post('user/delete-image', [UserController::class, 'deleteImage']);

    // ======================
    // 📊 Dashboard
    // ======================
    Route::get('dashboard/summary', [DashboardController::class, 'summary']);

    // ======================
    // 🚨 ALERTAS
    // ======================
    Route::prefix('alerts')->group(function () {
        Route::get('/', [AlertController::class, 'index']);
        Route::get('/{id}', [AlertController::class, 'show']);
        Route::get('/statistics', [AlertController::class, 'stats']);
        Route::get('/filter-options', [AlertController::class, 'filterOptions']);
        Route::post('/check-all', [AlertController::class, 'checkAll']);
        Route::patch('/{id}/resolve', [AlertController::class, 'resolve']);
        Route::put('/{id}/status', [AlertController::class, 'updateStatus']);
        Route::post('/{id}/create-order', [AlertController::class, 'createOrder']);
    });

    // ======================
    // 🛍️ ÓRDENES
    // ======================
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::get('/statistics', [OrderController::class, 'statistics']);
        Route::get('/supplier/{supplierId}', [OrderController::class, 'bySupplier']);
        Route::get('/product/{productId}', [OrderController::class, 'byProduct']);
        Route::get('/{id}', [OrderController::class, 'show']);
        Route::post('/', [OrderController::class, 'store']);
        Route::put('/{id}', [OrderController::class, 'update']);
        Route::patch('/{id}/status', [OrderController::class, 'updateStatus']);
        Route::delete('/{id}', [OrderController::class, 'destroy']);
        Route::post('/{id}/resend-email', [OrderController::class, 'resendEmail']);
    });

    // ======================
    // 🧾 Categorías
    // ======================
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::post('/', [CategoryController::class, 'store']);
        Route::post('init', [CategoryController::class, 'init']);
        Route::post('sync', [CategoryController::class, 'sync']);
        Route::get('{id}', [CategoryController::class, 'show']);
    });

    // ======================
    // 📦 Productos
    // ======================
    Route::prefix('products')->group(function () {
        Route::get('{productId}/suppliers', [ProductController::class, 'getSuppliers']);
        Route::post('{productId}/attach-suppliers', [ProductSupplierController::class, 'attachSuppliersToProduct']);
    });

    Route::apiResource('products', ProductController::class);
    Route::apiResource('product-details', ProductDetailController::class);

    // ======================
    // 📦 Inventarios
    // ======================
    Route::prefix('inventories')->group(function () {
        Route::get('summary', [InventoryController::class, 'summary']);
        Route::post('{id}/adjust', [InventoryController::class, 'adjustStock']);
    });

    Route::apiResource('inventories', InventoryController::class);
    Route::apiResource('inventory-details', InventoryDetailController::class);

    // ======================
    // 🏭 Almacenes y Ubicaciones
    // ======================
    Route::apiResource('warehouses', WarehouseController::class);
    Route::apiResource('locations', LocationController::class);

    // ======================
    // 📥 Entradas
    // ======================
    Route::prefix('entries')->group(function () {
        Route::get('lots-summary', [EntryController::class, 'lotsSummary']);
        Route::get('summary', [EntryController::class, 'summary']);
        Route::get('form-data', [EntryController::class, 'formData']);
    });

    Route::apiResource('entries', EntryController::class);
    Route::apiResource('entry-notes', EntryNoteController::class);

    // ======================
    // 📤 Salidas
    // ======================
    Route::prefix('outputs')->group(function () {
        Route::get('summary', [OutputController::class, 'summary']);
        Route::get('form-data', [OutputController::class, 'formData']);
    });

    Route::apiResource('outputs', OutputController::class);

    // ======================
    // 🧾 Dependencias de Compra
    // ======================
    Route::apiResource('dep-buys', DepBuyController::class);

    // ======================
    // 🧑‍🤝‍🧑 Proveedores
    // ======================
    Route::prefix('suppliers')->group(function () {
        Route::post('{supplierId}/attach-products', [ProductSupplierController::class, 'attachProductsToSupplier']);

        Route::get('{supplier}/products', [SupplierController::class, 'getProducts']);
        Route::post('{supplier}/products/attach', [SupplierController::class, 'attachProducts']);
        Route::post('{supplier}/products', [SupplierController::class, 'syncProducts']);
        Route::delete('{supplier}/products/{product}', [SupplierController::class, 'detachProduct']);
    });

    Route::apiResource('suppliers', SupplierController::class);

    // ======================
    // 👑 Administración (solo admin)
    // ======================
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::post('users/{id}/change-password', [UserController::class, 'changePassword']);
        Route::apiResource('roles', RoleController::class);
    });
});

// ======================
// 📲 Notificaciones Firebase (FCM)
// ======================
Route::post('/enviar-notificacion', [NotificacionesController::class, 'enviarNotificacion']);
