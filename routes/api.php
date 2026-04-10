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
        
        // ==============================================
        // ADMIN ONLY ROUTES
        // ==============================================
        Route::middleware('role:admin')->group(function () {
            // Users
            Route::apiResource('users', UserController::class);
            Route::post('users/{user}/roles', [UserController::class, 'assignRole']);
            Route::delete('users/{user}/roles/{roleName}', [UserController::class, 'removeRole']);

            // Roles & Permissions
            Route::apiResource('roles', RoleController::class);
            Route::post('roles/{role}/permissions', [RoleController::class, 'syncPermissions']);
            Route::get('permissions', [PermissionController::class, 'index']);
            
            // Delete Products/Categories (Admin only based on previous rule)
            Route::delete('categories/{category}', [CategoryController::class, 'destroy']);
            Route::delete('products/{product}', [ProductController::class, 'destroy']);
            
            // Suppliers
            Route::apiResource('fournisseurs', FournisseurController::class);
            
            // Analytics
            Route::apiResource('previsions', PrevisionController::class)->except(['update']);
            Route::apiResource('historique-ventes', HistoriqueVentesController::class)->only(['index', 'store']);
            Route::get('dashboard', [ReportController::class, 'dashboard']);
            Route::get('rapports/{rapport}/download', [ReportController::class, 'download'])->name('rapports.download');
            Route::apiResource('rapports', ReportController::class)->except(['update']);
            
            // Alert Configs
            Route::apiResource('alertes', AlerteController::class)->except(['store', 'update', 'index', 'show']);
        });

        // ==============================================
        // SHARED ROUTES (Admin & User)
        // ==============================================

        // Products & Categories (Read/Update/Create)
        Route::get('products/low-stock', [ProductController::class, 'index'])->name('products.low-stock');
        Route::apiResource('categories', CategoryController::class)->except(['destroy']);
        Route::apiResource('products', ProductController::class)->except(['destroy']);
        Route::post('products/{product}/image', [ProductController::class, 'uploadImage']);

        // Orders
        Route::apiResource('commandes', CommandeController::class);
        Route::patch('commandes/{commande}/statut', [CommandeController::class, 'updateStatut']);

        // Stock Movements
        Route::get('mouvements/entrees', [MouvementStockController::class, 'entries']);
        Route::get('mouvements/sorties', [MouvementStockController::class, 'exits']);
        Route::apiResource('mouvements', MouvementStockController::class)->only(['index', 'store', 'show']);

        // Alerts (View & Resolve)
        Route::get('alertes/actives', [AlerteController::class, 'actives']);
        Route::get('alertes', [AlerteController::class, 'index']);
        Route::get('alertes/{alerte}', [AlerteController::class, 'show']);
        Route::patch('alertes/{alerte}/resolve', [AlerteController::class, 'resolve']);

        // Notifications (Personal)
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::patch('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::patch('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::apiResource('notifications', NotificationController::class)->only(['index', 'destroy']);

    });
});
