<?php

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Http\Controllers\InventoryController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Dashboard route (ensure you create DashboardController if needed)
Route::get('/logout', [InventoryController::class, 'logout'])
    ->name('logout');



// Logout route
Route::get('/logout', [InventoryController::class, 'logout'])
    ->name('logout');

// Inventory routes
Route::prefix('inventory')
    ->middleware('auth')
    ->name('inventory.') 
    ->group(function () {

        Route::get('/dashboard', [InventoryController::class, 'index'])
            ->name('dashboard'); 

        Route::prefix('brands')
            ->name('brands.')
            ->group(function () {
                Route::get('/', [InventoryController::class, 'brands'])
                ->name('list');

                Route::get('/metrics', [InventoryController::class, 'brands_metrics'])
                ->name('metrics');

                Route::get('/datatable', [InventoryController::class, 'brands_datatable'])
                ->name('datatable');

                Route::post('/store', [InventoryController::class, 'store_brand'])
                ->name('store');

                Route::put('/{brand}', [InventoryController::class, 'update_brand'])
                ->name('update');

                Route::delete('/{brand}', [InventoryController::class, 'destroy_brand'])
                ->name('destroy');
            });

        Route::prefix('manufacturers')
            ->name('manufacturers.')
            ->group(function () {
                Route::get('/', [InventoryController::class, 'manufacturers'])
                ->name('list');

                Route::get('/metrics', [InventoryController::class, 'get_manufacturers_metrics'])
                ->name('metrics');

                Route::get('/datatable', [InventoryController::class, 'manufacturers_datatable'])
                ->name('datatable');

                Route::post('/store', [InventoryController::class, 'store_manufacturer'])
                ->name('store');

                Route::delete('/{manufacturer_id}', [InventoryController::class, 'destroy_manufacturer'])
                ->name('delete');

                Route::put('/{manufacturer_id}', [InventoryController::class, 'update_manufacturer'])
                ->name('update');
            });

        Route::prefix('raw-materials')
            ->name('raw-materials.')
            ->group(function () {
            Route::get('/', [InventoryController::class, 'raw_materials'])
                ->name('list'); 

            Route::get('/attributes', [InventoryController::class, 'raw_materials_attributes'])
                ->name('attributes');  

            Route::prefix('categories')
                ->name('categories.')
                ->group(function () {
                    Route::get('/', [InventoryController::class, 'raw_materials_categories'])
                    ->name('list'); 

                    Route::get('/metrics', [InventoryController::class, 'raw_materials_metrics'])
                    ->name('metrics'); 

                    Route::get('/datatable', [InventoryController::class, 'raw_materials_categories_datatable'])
                    ->name('datatable'); 

                    Route::put('/', [InventoryController::class, 'update_raw_materials_categories'])
                    ->name('update'); 

                    Route::post('/', [InventoryController::class, 'store_raw_materials_categories'])
                    ->name('store'); 
            });

            Route::get('/datatable', [InventoryController::class, 'raw_materials_datatable'])
                ->name('datatable'); 

            // ✅ DeRfine the correct metrics route separately
            Route::get('/metrics', [InventoryController::class, 'get_raw_materials_metrics'])
                ->name('raw-materials.metrics'); 

            // ✅ Define the correct metrics route separately
            Route::post('/', [InventoryController::class, 'store_raw_material'])
                ->name('raw-materials.store'); 
        });

        Route::prefix('products')
            ->name('products.')
            ->group(function () {
            Route::get('/', [InventoryController::class, 'products'])
                ->name('list'); 

            Route::get('/attributes', [InventoryController::class, 'products_attributes'])
                ->name('attributes'); 

            Route::get('/brands', [InventoryController::class, 'products_brands'])
                ->name('brands'); 

            Route::get('/categories', [InventoryController::class, 'products_categories'])
                ->name('categories'); 

            Route::get('/datatable', [InventoryController::class, 'products_datatable'])
                ->name('products.list'); 

            // ✅ Define the correct metrics route separately
            Route::get('/metrics', [InventoryController::class, 'get_products_metrics'])
                ->name('products.metrics'); 

            // ✅ Define the correct metrics route separately
            Route::post('/', [InventoryController::class, 'store_product'])
                ->name('products.store'); 

                Route::prefix('categories')
                ->name('categories.')
                ->group(function () {
                    Route::get('/', [InventoryController::class, 'update_products_categories'])
                    ->name('list'); 

                    Route::get('/metrics', [InventoryController::class, 'get_products_categories_metrics'])
                    ->name('metrics'); 

                    Route::get('/datatable', [InventoryController::class, 'products_categories_datatable'])
                    ->name('datatable'); 

                    Route::put('/', [InventoryController::class, 'update_products_categories'])
                    ->name('update'); 

                    Route::post('/', [InventoryController::class, 'store_products_categories'])
                    ->name('store'); 
            });

        });

        Route::prefix('suppliers')
        ->group(function () {
        Route::get('/', [InventoryController::class, 'raw_materials'])
            ->name('suppliers'); 
        });
    });

   

// Inventory resource routes
Route::resource('inventory', InventoryController::class)
    ->middleware('auth')
    ->names('inventory');

// Sales resource routes
Route::resource('sales', SalesController::class)
    ->middleware('auth')
    ->names('sales');

// Reports route
Route::get('/reports', [ReportsController::class, 'index'])
    ->middleware('auth')
    ->name('reports.index');