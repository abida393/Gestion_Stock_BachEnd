<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\FournisseurController;
use App\Http\Controllers\Api\CommandeController;
use App\Http\Controllers\Api\MouvementStockController;
use App\Http\Controllers\Api\AlerteController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PrevisionController;
use App\Http\Controllers\Api\HistoriqueVentesController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // Authentication
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
            Route::put('me', [AuthController::class, 'updateProfile']);
        });
    });

    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {
        
        // Users
        Route::apiResource('users', UserController::class);
        Route::post('users/{user}/roles', [UserController::class, 'assignRole']);
        Route::delete('users/{user}/roles/{roleId}', [UserController::class, 'removeRole']);

        // Roles & Permissions
        Route::apiResource('roles', RoleController::class);
        Route::post('roles/{role}/permissions', [RoleController::class, 'syncPermissions']);
        Route::get('permissions', [PermissionController::class, 'index']);

        // Products & Categories
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('products', ProductController::class);
        Route::post('products/{product}/image', [ProductController::class, 'uploadImage']);
        Route::get('products/low-stock', [ProductController::class, 'index'])->name('products.low-stock'); // Filter logic handles it

        // Suppliers & Orders
        Route::apiResource('fournisseurs', FournisseurController::class);
        Route::apiResource('commandes', CommandeController::class);
        Route::patch('commandes/{commande}/statut', [CommandeController::class, 'updateStatut']);

        // Stock Movements
        Route::get('mouvements/entrees', [MouvementStockController::class, 'entries']);
        Route::get('mouvements/sorties', [MouvementStockController::class, 'exits']);
        Route::apiResource('mouvements', MouvementStockController::class)->only(['index', 'store', 'show']);

        // Alerts
        Route::get('alertes/actives', [AlerteController::class, 'actives']);
        Route::patch('alertes/{alerte}/resolve', [AlerteController::class, 'resolve']);
        Route::apiResource('alertes', AlerteController::class)->except(['store', 'update']);

        // Notifications
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::patch('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::patch('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::apiResource('notifications', NotificationController::class)->only(['index', 'destroy']);

        // Forecasts & Sales History
        Route::apiResource('previsions', PrevisionController::class)->except(['update']);
        Route::apiResource('historique-ventes', HistoriqueVentesController::class)->only(['index', 'store']);

        // Reports & Dashboard
        Route::get('dashboard', [ReportController::class, 'dashboard']);
        Route::get('rapports/{rapport}/download', [ReportController::class, 'download'])->name('rapports.download');
        Route::apiResource('rapports', ReportController::class)->except(['update']);

    });
});
