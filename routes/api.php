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
            // dashboard moved to shared routes below
            Route::get('rapports/{rapport}/download', [ReportController::class, 'download'])->name('rapports.download');
            Route::apiResource('rapports', ReportController::class)->except(['update']);
            
            // Alert Configs
            Route::apiResource('alertes', AlerteController::class)->except(['store', 'update', 'index', 'show']);
        });

        // ==============================================
        // SHARED ROUTES (Admin & User)
        // ==============================================

        // Products & Categories (Read for all users)
        Route::get('products/low-stock', [ProductController::class, 'index'])->name('products.low-stock');
        Route::get('products', [ProductController::class, 'index']);
        Route::get('products/{product}', [ProductController::class, 'show']);
        Route::get('categories', [CategoryController::class, 'index']);
        Route::get('categories/{category}', [CategoryController::class, 'show']);

        // Products & Categories (Create/Update - Admin only)
        Route::middleware('role:admin')->group(function () {
            Route::post('products', [ProductController::class, 'store']);
            Route::put('products/{product}', [ProductController::class, 'update']);
            Route::patch('products/{product}', [ProductController::class, 'update']);
            Route::post('products/{product}/image', [ProductController::class, 'uploadImage']);
            Route::post('categories', [CategoryController::class, 'store']);
            Route::put('categories/{category}', [CategoryController::class, 'update']);
            Route::patch('categories/{category}', [CategoryController::class, 'update']);
        });

        // Orders
        Route::apiResource('commandes', CommandeController::class);
        Route::patch('commandes/{commande}/statut', [CommandeController::class, 'updateStatut']);

        // Stock Movements - Create and List are open to all (List is filtered by user in Controller)
        Route::post('mouvements', [MouvementStockController::class, 'store']);
        Route::get('mouvements/entrees', [MouvementStockController::class, 'entries']);
        Route::get('mouvements/sorties', [MouvementStockController::class, 'exits']);
        Route::get('mouvements', [MouvementStockController::class, 'index']);
        Route::get('mouvements/{mouvement}', [MouvementStockController::class, 'show']);

        // Dashboard KPIs — all authenticated users
        Route::get('dashboard', [ReportController::class, 'dashboard']);

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


