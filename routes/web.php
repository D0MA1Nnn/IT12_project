<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductUnitController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UnitOfMeasureController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Root
|--------------------------------------------------------------------------
*/

Route::redirect('/', '/login');


/*
|--------------------------------------------------------------------------
| Guest
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {

    Route::get(
        '/login',
        [AuthController::class, 'showLogin']
    )->name('login');

    Route::post(
        '/login',
        [AuthController::class, 'login']
    )->name('login.submit');

});


/*
|--------------------------------------------------------------------------
| Authenticated
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    Route::get(
        '/dashboard',
        [DashboardController::class, 'index']
    )->name('dashboard');


    Route::post(
        '/logout',
        [AuthController::class, 'logout']
    )->name('logout');


    /*
    |--------------------------------------------------------------------------
    | OWNER
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:OWNER')->group(function () {


        /*
        |--------------------------------------------------------------------------
        | Categories
        |--------------------------------------------------------------------------
        */

        Route::resource(
            'categories',
            CategoryController::class
        )->except([
            'show',
            'destroy',
        ]);


        Route::patch(
            '/categories/{category}/deactivate',
            [CategoryController::class, 'deactivate']
        )->name('categories.deactivate');


        Route::patch(
            '/categories/{category}/activate',
            [CategoryController::class, 'activate']
        )->name('categories.activate');


        /*
        |--------------------------------------------------------------------------
        | Products
        |--------------------------------------------------------------------------
        */

        Route::resource(
            'products',
            ProductController::class
        )->except([
            'show',
            'destroy',
        ]);


        Route::patch(
            '/products/{product}/deactivate',
            [ProductController::class, 'deactivate']
        )->name('products.deactivate');


        Route::patch(
            '/products/{product}/activate',
            [ProductController::class, 'activate']
        )->name('products.activate');


        /*
        |--------------------------------------------------------------------------
        | Product Units
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/products/{product}/units',
            [ProductUnitController::class, 'index']
        )->name('products.units.index');


        Route::post(
            '/products/{product}/units',
            [ProductUnitController::class, 'store']
        )->name('products.units.store');


        Route::put(
            '/products/{product}/units/{productUnit}',
            [ProductUnitController::class, 'update']
        )->name('products.units.update');


        Route::patch(
            '/products/{product}/units/{productUnit}/toggle',
            [ProductUnitController::class, 'toggle']
        )->name('products.units.toggle');


        /*
        |--------------------------------------------------------------------------
        | Inventory
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/inventory',
            [InventoryController::class, 'index']
        )->name('inventory.index');


        Route::put(
            '/inventory/{inventory}',
            [InventoryController::class, 'update']
        )->name('inventory.update');


        /*
        |--------------------------------------------------------------------------
        | Units of Measure
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/units',
            [UnitOfMeasureController::class, 'index']
        )->name('units.index');


        Route::post(
            '/units',
            [UnitOfMeasureController::class, 'store']
        )->name('units.store');


        Route::put(
            '/units/{unit}',
            [UnitOfMeasureController::class, 'update']
        )->name('units.update');


        Route::patch(
            '/units/{unit}/toggle',
            [UnitOfMeasureController::class, 'toggle']
        )->name('units.toggle');


        /*
        |--------------------------------------------------------------------------
        | Suppliers
        |--------------------------------------------------------------------------
        */

        Route::resource(
            'suppliers',
            SupplierController::class
        )->except([
            'show',
            'destroy',
        ]);


        Route::patch(
            '/suppliers/{supplier}/toggle',
            [SupplierController::class, 'toggle']
        )->name('suppliers.toggle');


        /*
        |--------------------------------------------------------------------------
        | Purchasing
        |--------------------------------------------------------------------------
        */

        Route::resource(
            'purchases',
            PurchaseController::class
        )->only([
            'index',
            'create',
            'store',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Users
        |--------------------------------------------------------------------------
        */

        Route::resource(
            'users',
            UserController::class
        )->except([
            'show',
            'destroy',
        ]);


        Route::patch(
            '/users/{user}/toggle',
            [UserController::class, 'toggle']
        )->name('users.toggle');


        /*
        |--------------------------------------------------------------------------
        | Transactions
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/transactions',
            [TransactionController::class, 'index']
        )->name('transactions.index');


        /*
        |--------------------------------------------------------------------------
        | Activity Logs
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/activity-logs',
            [ActivityLogController::class, 'index']
        )->name('activity.index');


        /*
        |--------------------------------------------------------------------------
        | Backup & Recovery
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/backup',
            [BackupController::class, 'index']
        )->name('backup.index');


        Route::get(
            '/backup/create',
            [BackupController::class, 'create']
        )->name('backup.create');


        Route::post(
            '/backup/restore',
            [BackupController::class, 'restore']
        )->name('backup.restore');

    });


    /*
    |--------------------------------------------------------------------------
    | OWNER + SALES CLERK
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'role:OWNER,SALES_CLERK'
    )->group(function () {

        Route::resource(
            'sales',
            SaleController::class
        )->only([
            'index',
            'create',
            'store',
        ]);

        Route::patch(
            '/sales/{sale}/complete-delivery',
            [SaleController::class, 'completeDelivery']
        )->name('sales.complete-delivery');

        Route::patch(
            '/sales/{sale}/cancel-delivery',
            [SaleController::class, 'cancelDelivery']
        )->name('sales.cancel-delivery');

    });

});