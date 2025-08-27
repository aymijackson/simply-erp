<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ERPController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\SettingController;

use Modules\CRM\Http\Controllers\ActivityController;
use Modules\CRM\Http\Controllers\CustomerController;
use Modules\CRM\Http\Controllers\InteractionController;
use Modules\CRM\Http\Controllers\LeadController;
use Modules\CRM\Http\Controllers\NotesController;
use Modules\CRM\Http\Controllers\OpportunityController;
use Modules\CRM\Http\Controllers\SupportTicketController;

use Modules\HRM\Http\Controllers\EmployeeController;
use Modules\HRM\Http\Controllers\PayrollController;
use Modules\HRM\Http\Controllers\TrainingController;
use Modules\HRM\Http\Controllers\PerformanceController;

use Modules\Inventory\Http\Controllers\BrandController;
use Modules\Inventory\Http\Controllers\CustomerReturnController;
use Modules\Inventory\Http\Controllers\SupplierReturnController;
use Modules\Inventory\Http\Controllers\ManufacturerController;
use Modules\Inventory\Http\Controllers\ProductController;
use Modules\Inventory\Http\Controllers\StockAgingController;
use Modules\Inventory\Http\Controllers\StockController;
use Modules\Inventory\Http\Controllers\StockDashboardController;
use Modules\Inventory\Http\Controllers\StockIssueController;
use Modules\Inventory\Http\Controllers\StockLevelController;
use Modules\Inventory\Http\Controllers\StockTransferController;
use Modules\Inventory\Http\Controllers\UnitController;

use Modules\Production\Http\Controllers\BomController;
use Modules\Production\Http\Controllers\BomDeficitController;
use Modules\Production\Http\Controllers\BomDeficitTransferController;
use Modules\Production\Http\Controllers\BOMItemController;
use Modules\Production\Http\Controllers\WorkOrderController;     
use Modules\Production\Http\Controllers\RawMaterialController;
use Modules\Production\Http\Controllers\RoutingController;
use Modules\Production\Http\Controllers\RoutingStepController;
use Modules\Production\Http\Controllers\WorkOrderMaterialController;
use Modules\Production\Http\Controllers\WorkOrderStepController;
use Modules\Production\Http\Controllers\WorkOrderCostTypeController;
use Modules\Production\Http\Controllers\WorkOrderCostLineController;
use Modules\Production\Http\Controllers\WorkOrderMaterialsController;
use Modules\Production\Http\Controllers\WorkOrderTaskDependenciesController;
use Modules\Production\Http\Controllers\WorkOrderTaskController;
use Modules\Production\Http\Controllers\WorkOrderTaskTimeLogsController;

use Modules\Sales\Http\Controllers\SalesController;
;


use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ReportController;

require __DIR__.'/auth.php';

Route::get('/states/by-country/{country_id}', [LocationController::class, 'getStates']);
Route::get('/cities/by-state/{state_id}', [LocationController::class, 'getCities']);
Route::get('/brands/by-manufacturer/{manufacturer_id}', [ProductController::class, 'brandsByManufacturer']);


// ERP Main Admin Panel
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function() {
    Route::get('/dashboard', [ERPController::class, 'index'])->name('dashboard');
    Route::get('/regions/{id}/subregions', [ERPController::class, 'getSubregions']);
    Route::get('cities/search', [ERPController::class, 'searchCities'])->name('cities.search');
    Route::get('/locations/{location}/rooms', [ERPController::class, 'getRoomsByLocation']);


    // Core Components Management
    Route::prefix('companies')->name('companies.')->group(function () {

          // Core Components Management
          Route::prefix('departments')->name('departments.')->group(function () {
               Route::get('/', [CompanyController::class, 'departmentsIndex'])->name('index');
               Route::post('/', [CompanyController::class, 'storeDepartment'])->name('store');
               Route::get('/list', [CompanyController::class, 'departmentsDatatable'])->name('datatable');
               Route::get('/{id}/edit', [CompanyController::class, 'editDepartment'])->name('edit');
               Route::put('/{id}', [CompanyController::class, 'updateDepartment'])->name('update');
               Route::delete('/{department}', [CompanyController::class, 'destroyDepartment'])->name('destroy');
               Route::post('/bulk-delete', [CompanyController::class, 'bulkDeleteDepartment'])->name('bulk-delete');
          });

          Route::get('/', [CompanyController::class, 'index'])->name('index');
          Route::post('/', [CompanyController::class, 'store'])->name('store');
          Route::get('/list', [CompanyController::class, 'list'])->name('users.list');
          Route::get('/{id}/edit', [CompanyController::class, 'edit'])->name('edit');
          Route::put('/{id}', [CompanyController::class, 'update'])->name('update');
          Route::delete('/{id}', [CompanyController::class, 'destroy'])->name('destroy');
          Route::post('/bulk-delete', [CompanyController::class, 'bulkDelete'])->name('bulk-delete');
    });

    Route::prefix('cities')->name('cities.')->group(function () {
          Route::get('/', [ERPController::class, 'citiesIndex'])->name('index');
          Route::post('/', [ERPController::class, 'storeCity'])->name('store');
          Route::get('/list', [ERPController::class, 'citiesList'])->name('list');
          Route::get('/{id}/edit', [ERPController::class, 'editCity'])->name('edit');
          Route::put('/{id}', [ERPController::class, 'updateCity'])->name('update');
          Route::delete('/{id}', [ERPController::class, 'destroyCity'])->name('destroy');
          Route::post('/bulk-delete', [ERPController::class, 'bulkDeleteCities'])->name('bulk-delete');     
     });

    Route::prefix('countries')->name('countries.')->group(function () {
          Route::get('/', [ERPController::class, 'countriesIndex'])->name('index');
          Route::post('/', [ERPController::class, 'storeCountry'])->name('store');
          Route::get('/list', [ERPController::class, 'countriesList'])->name('list');
          Route::get('/{id}/edit', [ERPController::class, 'editCountry'])->name('edit');
          Route::put('/{id}', [ERPController::class, 'updateCountry'])->name('update');
          Route::post('/bulk-delete', [ERPController::class, 'bulkDeleteCountries'])->name('bulk-delete');  
          Route::delete('/{id}', [ERPController::class, 'destroyCountry'])->name('destroy');             
     });

    Route::prefix('locations')->name('locations.')->group(function () {
          Route::get('/', [ERPController::class, 'locationsIndex'])->name('index');
          Route::get('/list', [ERPController::class, 'locationsList'])->name('list');
          Route::get('/{location}', [ERPController::class, 'showLocation'])->name('show');
          Route::get('/{location}/blocks', [ERPController::class, 'locationBlocks']);
          Route::get('/{location}/floors', [ERPController::class, 'locationFloors']);
          Route::get('/{location}/rooms', [ERPController::class, 'locationRooms']);
          Route::get('/{location}/stores', [ERPController::class, 'locationStores']);
          Route::get('/{location}/shelves', [ERPController::class, 'locationShelves']);
          Route::post('/', [ERPController::class, 'storeLocation'])->name('store');
          Route::get('/{id}/edit', [ERPController::class, 'editLocation'])->name('edit');
          Route::put('/{id}', [ERPController::class, 'updateLocation'])->name('update');
          Route::post('/bulk-delete', [ERPController::class, 'bulkDeleteLocations'])->name('bulk-delete');  
          Route::delete('/{id}', [ERPController::class, 'destroyLocation'])->name('destroy');             
     });

    Route::prefix('location_blocks')->name('location_blocks.')->group(function () {
          Route::get('/', [ERPController::class, 'locationBlocksIndex'])->name('index');
          Route::post('/', [ERPController::class, 'storeLocationBlock'])->name('store');
          Route::get('/list', [ERPController::class, 'locationBlocksList'])->name('list');
          Route::get('/{id}/edit', [ERPController::class, 'editLocationBlock'])->name('edit');
          Route::put('/{id}', [ERPController::class, 'updateLocationBlock'])->name('update');
          Route::post('/bulk-delete', [ERPController::class, 'bulkDeleteLocationBlock'])->name('bulk-delete');  
          Route::delete('/{id}', [ERPController::class, 'destroyLocationBlock'])->name('destroy');             
     });

    Route::prefix('location_floors')->name('location_floors.')->group(function () {
          Route::get('/', [ERPController::class, 'locationBlockFloorsIndex'])->name('index');
          Route::post('/', [ERPController::class, 'storeLocationBlockFloor'])->name('store');
          Route::get('/list', [ERPController::class, 'locationBlockFloorsList'])->name('list');
          Route::get('/{id}/edit', [ERPController::class, 'editLocationBlockFloor'])->name('edit');
          Route::put('/{id}', [ERPController::class, 'updateLocationBlockFloor'])->name('update');
          Route::post('/bulk-delete', [ERPController::class, 'bulkDeleteLocationBlockFloors'])->name('bulk-delete');  
          Route::delete('/{id}', [ERPController::class, 'destroyLocationBlockFloor'])->name('destroy');             
     });

    Route::prefix('location_rooms')->name('location_rooms.')->group(function () {
          Route::get('/', [ERPController::class, 'locationBlockFloorRoomsIndex'])->name('index');
          Route::post('/', [ERPController::class, 'storeLocationRoom'])->name('store');
          Route::get('/list', [ERPController::class, 'locationRoomsList'])->name('list');
          Route::get('/{id}/edit', [ERPController::class, 'editLocationRoom'])->name('edit');
          Route::put('/{id}', [ERPController::class, 'updateLocationRoom'])->name('update');
          Route::post('/bulk-delete', [ERPController::class, 'bulkDeleteLocationRooms'])->name('bulk-delete');  
          Route::delete('/{id}', [ERPController::class, 'destroyLocationRoom'])->name('destroy');             
     });

    Route::prefix('location_stores')->name('location_stores.')->group(function () {
          Route::get('/', [ERPController::class, 'storesIndex'])->name('index');
          Route::post('/', [ERPController::class, 'storeStore'])->name('store');
          Route::get('/list', [ERPController::class, 'storesList'])->name('list');
          Route::get('/{id}/edit', [ERPController::class, 'editStore'])->name('edit');
          Route::put('/{id}', [ERPController::class, 'updateStore'])->name('update');
          Route::post('/bulk-delete', [ERPController::class, 'bulkDeleteStores'])->name('bulk-delete');  
          Route::delete('/{id}', [ERPController::class, 'destroyStore'])->name('destroy');             
     });

    Route::prefix('location_rooms')->name('location_rooms.')->group(function () {
          Route::get('/', [ERPController::class, 'locationBlockFloorRoomsIndex'])->name('index');
          Route::post('/', [ERPController::class, 'storeLocationBlockFloorRoom'])->name('store');
          Route::get('/list', [ERPController::class, 'locationBlockFloorRoomsList'])->name('list');
          Route::get('/{id}/edit', [ERPController::class, 'editLocationBlockFloorRoom'])->name('edit');
          Route::put('/{id}', [ERPController::class, 'updateLocationBlockFloorRoom'])->name('update');
          Route::post('/bulk-delete', [ERPController::class, 'bulkDeleteLocationBlockFloorRooms'])->name('bulk-delete');  
          Route::delete('/{id}', [ERPController::class, 'destroyLocationBlockFloorRoom'])->name('destroy');             
     });

    Route::prefix('location_types')->name('location_types.')->group(function () {
          Route::get('/', [ERPController::class, 'locationTypesIndex'])->name('index');
          Route::post('/', [ERPController::class, 'storeLocationType'])->name('store');
          Route::get('/list', [ERPController::class, 'locationTypesList'])->name('list');
          Route::get('/{location_type}', [ERPController::class, 'showLocationType'])->name('locations.types.show');
          Route::get('/{location_type}/blocks', [ERPController::class, 'locationTypeBlocks']);
          Route::get('/{location_type}/floors', [ERPController::class, 'locationTypeFloors']);
          Route::get('/{location_type}/locations', [ERPController::class, 'locationTypeLocations']);
          Route::get('/{location_type}/rooms', [ERPController::class, 'locationTypeRooms']);
          Route::get('/{location_type}/stores', [ERPController::class, 'locationTypeStores']);
          Route::get('/{location_type}/shelves', [ERPController::class, 'locationTypeShelves']);
          Route::get('/{id}/edit', [ERPController::class, 'editLocationType'])->name('edit');
          Route::put('/{id}', [ERPController::class, 'updateLocationType'])->name('update');
          Route::post('/bulk-delete', [ERPController::class, 'bulkDeleteLocationTypes'])->name('bulk-delete');  
          Route::delete('/{id}', [ERPController::class, 'destroyLocationType'])->name('destroy');             
     });

     // routes/web.php
     Route::prefix('store_shelves')->name('store_shelves.')->group(function () {
          Route::get('/', [ERPController::class, 'shelvesIndex'])->name('index');
          Route::post('/', [ERPController::class, 'storeShelf'])->name('store');
          Route::get('/list', [ERPController::class, 'shelvesList'])->name('list');
          Route::get('/{id}/edit', [ERPController::class, 'editShelf'])->name('edit');
          Route::put('/{id}', [ERPController::class, 'updateShelf'])->name('update');
          Route::post('/bulk-delete', [ERPController::class, 'bulkDeleteShelves'])->name('bulk-delete');
          Route::delete('/{id}', [ERPController::class, 'destroyShelf'])->name('destroy');
     });

    Route::prefix('states')->name('states.')->group(function () {
          Route::get('/', [ERPController::class, 'statesIndex'])->name('index');
          Route::post('/', [ERPController::class, 'storeState'])->name('store');
          Route::get('/list', [ERPController::class, 'statesList'])->name('list');
          Route::get('/{id}/edit', [ERPController::class, 'editState'])->name('edit');
          Route::put('/{id}', [ERPController::class, 'updateState'])->name('update');
          Route::delete('/bulk-delete', [ERPController::class, 'bulkDeleteStates'])->name('bulk-delete'); 
          Route::delete('/{id}', [ERPController::class, 'destroyState'])->name('destroy');    
     });

    Route::prefix('regions')->name('regions.')->group(function () {
          Route::get('/', [ERPController::class, 'regionsIndex'])->name('index');
          Route::post('/', [ERPController::class, 'storeRegion'])->name('store');
          Route::get('/list', [ERPController::class, 'regionsList'])->name('list');
          Route::get('/{id}/edit', [ERPController::class, 'editRegion'])->name('edit');
          Route::put('/{id}', [ERPController::class, 'updateRegion'])->name('update');
          Route::delete('/bulk-delete', [ERPController::class, 'bulkDeleteRegions'])->name('bulk-delete');
          Route::delete('/{id}', [ERPController::class, 'destroyRegion'])->name('destroy');     
     });

    Route::prefix('subregions')->name('subregions.')->group(function () {
          Route::get('/', [ERPController::class, 'subregionsIndex'])->name('index');
          Route::post('/', [ERPController::class, 'storeSubregion'])->name('store');
          Route::get('/list', [ERPController::class, 'subregionsList'])->name('list');
          Route::get('/{id}/edit', [ERPController::class, 'editSubregion'])->name('edit');
          Route::put('/{id}', [ERPController::class, 'updateSubregion'])->name('update');
          Route::delete('/bulk-delete', [ERPController::class, 'bulkDeleteSubregions'])->name('bulk-delete');
          Route::delete('/{id}', [ERPController::class, 'destroySubregion'])->name('destroy');     
     });


//     // Inventory Management
//     Route::resource('raw-materials', InventoryController::class);
//     Route::resource('products', InventoryController::class);

//     // Sales
//     Route::resource('sales', SalesController::class);

//     // Suppliers
//     Route::resource('suppliers', SupplierController::class);

//     // Reports
//     Route::resource('reports', ReportController::class);

    // Admin Controls
    Route::delete('/users/bulk-delete', [UserController::class, 'bulkDelete'])->name('users.bulkDelete');
    Route::get('/users-list', [UserController::class, 'list'])->name('users.list');
    Route::resource('users', UserController::class);
    Route::resource('roles', RoleController::class);
    
    // Bulk delete modules
    Route::delete('modules/bulk-delete', [ModuleController::class, 'bulkDelete'])
         ->name('modules.bulkDelete');

    // Standard resource
    Route::resource('modules', ModuleController::class);

    // bulk-delete
    Route::delete('permissions/bulk-delete', [PermissionController::class, 'bulkDelete'])
         ->name('permissions.bulkDelete');
    Route::resource('permissions', PermissionController::class);
    Route::resource('modules', ModuleController::class);
    Route::resource('settings', SettingController::class);

    /** Finance */
    Route::prefix('finance')->name('finance.')->group(function () {
          Route::resource('chart_of_accounts', ChartOfAccountController::class);
          Route::resource('journals', JournalEntryController::class);
          Route::resource('invoices', InvoiceController::class);
          Route::resource('payments', PaymentController::class);
          Route::resource('expenses', ExpenseController::class);
          Route::resource('budgets', BudgetController::class);
          Route::resource('accounts', AccountController::class);
     });

     /** Sales */
     Route::prefix('sales')->name('sales.')->group(function () {
          Route::resource('orders', SalesOrderController::class);
          Route::resource('quotations', QuotationController::class);
          Route::resource('deliveries', DeliveryController::class);
          Route::resource('returns', ReturnController::class);
     });

     /** Procurement */
     Route::prefix('procurement')->name('procurement.')->group(function () {
          Route::resource('purchase_orders', PurchaseOrderController::class);
          Route::resource('receiving', ReceivingController::class);
          Route::resource('invoices', SupplierInvoiceController::class);
     });

     /** CRM */
     Route::prefix('crm')->name('crm.')->group(function () {
          Route::prefix('activities')->name('activities.')->group(function () {
               Route::get('/', [ActivityController::class, 'index'])->name('index');
               Route::post('/', [ActivityController::class, 'store'])->name('store');
               Route::get('/datatable', [ActivityController::class, 'datatable'])->name('datatable');
               Route::get('/{id}/edit', [ActivityController::class, 'edit'])->name('edit');
               Route::put('/{id}', [ActivityController::class, 'update'])->name('update');
               Route::delete('/bulk-delete', [ActivityController::class, 'bulkDelete'])->name('bulk-delete');
               Route::delete('/{id}', [ActivityController::class, 'destroy'])->name('destroy');     
          });

          Route::prefix('customers')->name('customers.')->group(function () {

               Route::get('/select2', [CustomerController::class, 'select2'])->name('select2');
               Route::get('/', [CustomerController::class, 'index'])->name('index');
               Route::post('/', [CustomerController::class, 'store'])->name('store');
               Route::get('/datatable', [CustomerController::class, 'datatable'])->name('datatable');
               Route::get('/{id}/edit', [CustomerController::class, 'edit'])->name('edit');
               Route::put('/{id}', [CustomerController::class, 'update'])->name('update');
               Route::delete('/bulk-delete', [CustomerController::class, 'bulkDelete'])->name('bulk-delete');
               Route::delete('/{id}', [CustomerController::class, 'destroy'])->name('destroy');     
          });

          Route::prefix('interactions')->name('interactions.')->group(function () {
               Route::get('/fetch-interactables', [InteractionController::class, 'fetchInteractables'])->name('fetch.interactables');
               Route::get('/', [InteractionController::class, 'index'])->name('index');
               Route::post('/', [InteractionController::class, 'store'])->name('store');
               Route::get('/datatable', [InteractionController::class, 'datatable'])->name('datatable');
               Route::get('/{id}/edit', [InteractionController::class, 'edit'])->name('edit');
               Route::put('/{id}', [InteractionController::class, 'update'])->name('update');
               Route::delete('/bulk-delete', [InteractionController::class, 'bulkDelete'])->name('bulk-delete');
               Route::delete('/{id}', [InteractionController::class, 'destroy'])->name('destroy');     
          });

          Route::prefix('leads')->name('leads.')->group(function () {
               Route::get('/', [LeadController::class, 'index'])->name('index');
               Route::post('/', [LeadController::class, 'store'])->name('store');
               Route::get('/datatable', [LeadController::class, 'datatable'])->name('datatable');
               Route::get('/{id}/edit', [LeadController::class, 'edit'])->name('edit');
               Route::put('/{id}', [LeadController::class, 'update'])->name('update');
               Route::delete('/bulk-delete', [LeadController::class, 'bulkDelete'])->name('bulk-delete');
               Route::delete('/{id}', [LeadController::class, 'destroy'])->name('destroy');     
          });

          Route::prefix('notes')->name('notes.')->group(function () {
               Route::get('/fetch-notables', [NotesController::class, 'fetchNotables'])->name('fetch-notables');
               Route::get('/', [NotesController::class, 'index'])->name('index');
               Route::post('/', [NotesController::class, 'store'])->name('store');
               Route::get('/datatable', [NotesController::class, 'datatable'])->name('datatable');
               Route::get('/{id}/edit', [NotesController::class, 'edit'])->name('edit');
               Route::put('/{id}', [NotesController::class, 'update'])->name('update');
               Route::delete('/bulk-delete', [NotesController::class, 'bulkDelete'])->name('bulk-delete');
               Route::delete('/{id}', [NotesController::class, 'destroy'])->name('destroy');     
          });

          Route::prefix('opportunities')->name('opportunities.')->group(function () {
               Route::get('/', [OpportunityController::class, 'index'])->name('index');
               Route::post('/', [OpportunityController::class, 'store'])->name('store');
               Route::get('/datatable', [OpportunityController::class, 'datatable'])->name('datatable');
               Route::get('/{id}/edit', [OpportunityController::class, 'edit'])->name('edit');
               Route::put('/{id}', [OpportunityController::class, 'update'])->name('update');
               Route::delete('/bulk-delete', [OpportunityController::class, 'bulkDelete'])->name('bulk-delete');
               Route::delete('/{id}', [OpportunityController::class, 'destroy'])->name('destroy');     
          });

          Route::prefix('support-tickets')->name('support-tickets.')->group(function () {
               Route::get('/', [SupportTicketController::class, 'index'])->name('index');
               Route::post('/', [SupportTicketController::class, 'store'])->name('store');
               Route::get('/datatable', [SupportTicketController::class, 'datatable'])->name('datatable');
               Route::get('/{id}/edit', [SupportTicketController::class, 'edit'])->name('edit');
               Route::put('/{id}', [SupportTicketController::class, 'update'])->name('update');
               Route::delete('/bulk-delete', [SupportTicketController::class, 'bulkDelete'])->name('bulk-delete');
               Route::delete('/{id}', [SupportTicketController::class, 'destroy'])->name('destroy');     
          });

     });

     /** HRM */
     Route::prefix('hrm')->name('hrm.')->group(function () {
          Route::prefix('employees')->name('employees.')->group(function () {

               Route::prefix('attendances')->name('attendance.')->group(function () {
                    Route::get('/', [EmployeeController::class, 'attendancesIndex'])->name('index');
                    Route::post('/', [EmployeeController::class, 'storeAttendance'])->name('store');
                    Route::get('/list', [EmployeeController::class, 'attendancesDatatable'])->name('datatable');
                    Route::get('/{attendance}/edit', [EmployeeController::class, 'editAttendance'])->name('edit');
                    Route::put('/{attendance}', [EmployeeController::class, 'updateAttendance'])->name('update');
                    Route::delete('/bulk-delete', [EmployeeController::class, 'bulkDeleteAttendances'])->name('bulk-delete');
                    Route::delete('/{attendance}', [EmployeeController::class, 'destroyAttendance'])->name('destroy');      
               }); 

               Route::prefix('leaves')->name('leaves.')->group(function () {
                    Route::get('/', [EmployeeController::class, 'leavesIndex'])->name('index');
                    Route::post('/', [EmployeeController::class, 'storeLeave'])->name('store');
                    Route::get('/list', [EmployeeController::class, 'leavesDatatable'])->name('datatable');
                    Route::get('/{leave}/edit', [EmployeeController::class, 'editLeave'])->name('edit');
                    Route::put('/{leave}', [EmployeeController::class, 'updateLeave'])->name('update');
                    Route::post('/{leave}/approve', [EmployeeController::class, 'approveLeave'])->name('approve');
                    Route::post('/{leave}/reject', [EmployeeController::class, 'rejectLeave'])->name('reject');
                    Route::delete('/bulk-delete', [EmployeeController::class, 'bulkDeleteLeaves'])->name('bulk-delete');
                    Route::delete('/{leave}', [EmployeeController::class, 'destroyLeave'])->name('destroy');      
               }); 

               Route::prefix('performances')->name('performances.')->group(function () {
                    Route::get('/', [PerformanceController::class, 'index'])->name('index');
                    Route::post('/', [PerformanceController::class, 'store'])->name('store');
                    Route::get('/list', [PerformanceController::class, 'datatable'])->name('datatable');
                    Route::put('/{performance}', [PerformanceController::class, 'update'])->name('update');
                    Route::delete('/bulk-delete', [PerformanceController::class, 'bulkDelete'])->name('bulk-delete');
                    Route::delete('/{performance}', [PerformanceController::class, 'destroy'])->name('destroy');      
               }); 

               Route::prefix('trainings')->name('trainings.')->group(function () {
                    Route::get('/', [TrainingController::class, 'index'])->name('index');
                    Route::post('/', [TrainingController::class, 'store'])->name('store');
                    Route::get('/list', [TrainingController::class, 'datatable'])->name('datatable');
                    Route::get('/{training}/edit', [TrainingController::class, 'edit'])->name('edit');
                    Route::put('/{training}', [TrainingController::class, 'update'])->name('update');
                    Route::post('/{training}/approve', [TrainingController::class, 'approve'])->name('approve');
                    Route::post('/{training}/reject', [TrainingController::class, 'reject'])->name('reject');
                    Route::delete('/bulk-delete', [TrainingController::class, 'bulkDelete'])->name('bulk-delete');
                    Route::delete('/{training}', [TrainingController::class, 'destroy'])->name('destroy');      
               }); 

               Route::get('/', [EmployeeController::class, 'index'])->name('index');
               Route::post('/', [EmployeeController::class, 'store'])->name('store');
               Route::get('/select2', [EmployeeController::class, 'select2'])->name('datatable');
               Route::get('/list', [EmployeeController::class, 'datatable'])->name('datatable');
               Route::get('/{employee}/edit', [EmployeeController::class, 'edit'])->name('edit');
               Route::put('/{employee}', [EmployeeController::class, 'update'])->name('update');
               Route::delete('/bulk-delete', [EmployeeController::class, 'bulkDelete'])->name('bulk-delete');
               Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->name('destroy');      
          }); 
          
          Route::prefix('payroll')
          ->name('payroll.')
          ->group(function () {
               Route::get('export/{type}', [PayrollController::class, 'export'])->name('export');
               Route::get('slip/{id}', [PayrollController::class, 'slip'])->name('slip');

               Route::post('/generate-monthly', [PayrollController::class, 'generateMonthly'])->name('generate-monthly');

               Route::get('/',[PayrollController::class, 'index'      ])->name('index');
               Route::get('/datatable',   [PayrollController::class, 'datatable'  ])->name('datatable');
               Route::post('/',           [PayrollController::class, 'store'      ])->name('store');
               Route::put('/{payroll}',  [PayrollController::class, 'update'     ])->name('update');   // via _method=PUT
               Route::delete('/{payroll}',[PayrollController::class, 'destroy'    ])->name('destroy');
               Route::post('/{payroll}/paid', [PayrollController::class, 'markAsPaid'])->name('mark-paid');
               Route::post('/bulk-delete',[PayrollController::class, 'bulkDelete'])->name('bulk-delete');
               Route::post('/{payroll}/toggle-paid', [PayrollController::class, 'togglePaidStatus'])->name('toggle-paid');
          });
          
     });

     /** Sales */
     Route::prefix('sales')->name('sales.')->group(function () {
          Route::prefix('invoices')->name('invoices.')->group(function () {
               Route::prefix('lines')->name('lines.')->group(function () {
                    Route::get('/select2', [SalesController::class, 'select2'])->name('select2');
               });
          });
          Route::prefix('delivery')->name('delivery.')->group(function () {
               Route::prefix('lines')->name('lines.')->group(function () {
                    Route::get('/select2', [SalesController::class, 'select2'])->name('select2');
               });

               Route::get('/select2', [SalesController::class, 'select2'])->name('select2');
          });
     });

     /** Production */
     Route::prefix('production')->name('production.')->group(function () {
          
          Route::prefix('boms')->name('boms.')->group(function () {

               Route::prefix('headers')->name('headers.')->group(function () {
                    Route::get('/select2', [BomController::class, 'select2'])->name('select2');
               });

               Route::get('/{bom}/items/datatable', [BomController::class, 'bom_items_datatable'])->name('items_datatable');

               // Select2: variants on THIS BOM (with “available on BOM” in the label)
               Route::get('/{bom}/select2', [BomController::class, 'otherSelect2'])
               ->name('other-select2');

               // Select2: variants on THIS BOM (with “available on BOM” in the label)
               Route::get('/{bom}/items/select2', [BomDeficitTransferController::class, 'itemsSelect2'])
               ->name('items.select2');

               // Return { qty_available: ... } for current BOM + variant
               Route::get('/{bom}/available', [BomDeficitTransferController::class, 'available'])
                    ->name('available');

               // Perform the transfer (create deficit “borrow” rows)
               Route::post('{bom}/transfer', [BomDeficitTransferController::class, 'transfer'])
                    ->name('transfer');

               


               Route::prefix('items')->name('items.')->group(function () {
                    Route::get('/select2', [BOMItemController::class, 'select2'])->name('select2');
                    Route::get('/', [BOMItemController::class, 'index'])->name('index');
                    Route::post('/', [BOMItemController::class, 'store'])->name('store');
                    Route::get('/datatable', [BOMItemController::class, 'datatable'])->name('datatable');
                    Route::get('/{id}/edit', [BOMItemController::class, 'edit'])->name('edit');
                    Route::put('/{id}', [BOMItemController::class, 'update'])->name('update');
                    Route::delete('/bulk-delete', [BOMItemController::class, 'bulkDelete'])->name('bulk-delete');
                    Route::delete('/{id}', [BOMItemController::class, 'destroy'])->name('destroy');     
               });

               Route::prefix('deficits')->name('deficits.')->group(function () {
                    Route::prefix('transactions')->name('transactions.')->group(function () {
                         
                         // Transactions log
                         Route::get('/',            [BomDeficitController::class,'txnsIndex'])->name('index');
                         Route::get('/datatable',  [BomDeficitController::class,'txnsDatatable'])->name('datatable');
                    
                         // Create a transaction (repay / writeoff / adjust)
                         Route::post('/',           [BomDeficitController::class,'storeTxn'])->name('store');
                    
                         // Delete a transaction (restricted to last txn for that (bom,variant))
                         Route::delete('/{txn}',   [BomDeficitController::class,'destroyTxn'])->name('destroy');
                    });

                    // Deficits list
                    Route::get('/',                [BomDeficitController::class,'index'])->name('index');
                    Route::get('/datatable',       [BomDeficitController::class,'datatable'])->name('datatable');
                
                });

               Route::get('/', [BomController::class, 'index'])->name('index');
               Route::post('/', [BomController::class, 'store'])->name('store');
               Route::get('/datatable', [BomController::class, 'datatable'])->name('datatable');
               Route::get('/{bom}', [BomController::class, 'show'])->name('show');
               Route::put('/{bom}', [BomController::class, 'update'])->name('update');
               Route::delete('/bulk-delete', [BomController::class, 'bulkDelete'])->name('bulk-delete');
               Route::delete('/{bom}', [BomController::class, 'destroy'])->name('destroy');   
               
               
          });

          Route::prefix('raw-materials')->name('raw-materials.')->group(function () {

               Route::get('/', [RawMaterialController::class, 'index'])->name('index');
               Route::post('/', [RawMaterialController::class, 'store'])->name('store');
               Route::get('/datatable', [RawMaterialController::class, 'datatable'])->name('datatable');
               Route::get('/{raw_material}/edit', [RawMaterialController::class, 'edit'])->name('edit');
               Route::put('/{raw_material}', [RawMaterialController::class, 'update'])->name('update');
               Route::delete('/bulk-delete', [RawMaterialController::class, 'bulkDelete'])->name('bulk-delete');
               Route::delete('/{raw_material}', [RawMaterialController::class, 'destroy'])->name('destroy');                  
               
          });

          Route::prefix('routings')->name('routings.')->group(function () {

               Route::get('select2', [RoutingController::class, 'select2'])
               ->name('select2');

               Route::prefix('steps')->name('steps.')->group(function () {
                    Route::get('/', [RoutingStepController::class, 'index'])
                         ->name('index');

                    Route::get('datatable', [RoutingStepController::class, 'datatable'])
                         ->name('datatable');

                    Route::post('steps', [RoutingStepController::class, 'store'])
                         ->name('store');

                    Route::put('{step}', [RoutingStepController::class, 'update'])
                         ->name('update');

                    Route::delete('{step}', [RoutingStepController::class, 'destroy'])
                         ->name('destroy');

                    // Optional: bulk reorder by [{id, sequence},...]
                    Route::post('reorder', [RoutingStepController::class, 'reorder'])
                         ->name('reorder');     
               });

               
               Route::get('/', [RoutingController::class, 'index'])->name('index');
               Route::post('/', [RoutingController::class, 'store'])->name('store');
               Route::get('/datatable', [RoutingController::class, 'datatable'])->name('datatable');
               Route::put('/{id}', [RoutingController::class, 'update'])->name('update');
               Route::delete('/bulk-delete', [RoutingController::class, 'bulkDelete'])->name('bulk-delete');
               Route::delete('/{routing}', [RoutingController::class, 'destroy'])->name('destroy');   
               
               Route::get('/{routing}/edit', [RoutingController::class, 'edit'])->name('edit');
               Route::post('/{routing}/steps', [RoutingController::class, 'storeStep'])->name('store.steps');
               Route::get('/{routing}', [RoutingController::class, 'show'])->name('show');
               Route::get('/{routing}/steps/datatable', [RoutingController::class, 'stepsDatatable'])->name('steps.datatable');
               
          });

          Route::prefix('work-orders')->name('work-orders.')->group(function () {
               
               Route::get('tasks/{work_order}/select2',  [WorkOrderTaskController::class, 'workOrderTasksSelect2'])
               ->name('tasks.select2');
               
               
               Route::get('{work_order}/boms/items', [WorkOrderMaterialController::class, 'bomItemsDatatable'])->name('boms.items.datatable');

               Route::prefix('materials')->name('materials.')->group(function () {
                    Route::get('/', [WorkOrderMaterialController::class, 'index'])->name('index');
                    Route::post('/', [WorkOrderMaterialController::class, 'store'])->name('store');
                    Route::get('/datatable', [WorkOrderMaterialController::class, 'datatable'])->name('datatable');
                    Route::get('/{id}/edit', [WorkOrderMaterialController::class, 'edit'])->name('edit');
                    Route::put('/{id}', [WorkOrderMaterialController::class, 'update'])->name('update');
                    Route::delete('/bulk-delete', [WorkOrderMaterialController::class, 'bulkDelete'])->name('bulk-delete');
                    Route::delete('/{id}', [WorkOrderMaterialController::class, 'destroy'])->name('destroy');     
               });

                // datatable (you already have this)
               Route::get('{workOrder}/materials/datatable', [WorkOrderMaterialsController::class, 'datatable'])
               ->name('materials.datatable');

               // CRUD
               Route::post('{workOrder}/materials',  [WorkOrderMaterialsController::class, 'store'])->name('materials.store');
               Route::put('materials/{material}',    [WorkOrderMaterialsController::class, 'update'])->name('materials.update');
               Route::delete('materials/{material}', [WorkOrderMaterialsController::class, 'destroy'])->name('materials.destroy');

               // Select2 for product variants
               Route::get('{workOrder}/materials/variants/select2', [WorkOrderMaterialsController::class, 'variantsSelect2'])
               ->name('materials.variants.select2');

               // Lifecycle (already wired, keep)
               Route::post('materials/{material}/issue',  [WorkOrderMaterialsLifecycleController::class, 'issue'])->name('materials.issue');
               Route::post('materials/{material}/return', [WorkOrderMaterialsLifecycleController::class, 'return'])->name('materials.return');

               Route::prefix('routings')->name('routings.')->group(function () {
                    Route::prefix('steps')->name('steps.')->group(function () {
                         Route::get('datatable/{work_order}', [WorkOrderController::class, 'routingStepsDatatable'])->name('datatable');
                    });
               });

               Route::prefix('steps')->name('steps.')->group(function () {
                    Route::get('/', [WorkOrderStepController::class, 'index'])->name('index');
                    Route::post('/', [WorkOrderStepController::class, 'store'])->name('store');
                    Route::get('/datatable', [WorkOrderStepController::class, 'datatable'])->name('datatable');
                    Route::get('/{id}/edit', [WorkOrderStepController::class, 'edit'])->name('edit');
                    Route::put('/{step}', [WorkOrderStepController::class, 'update'])->name('update');
                    Route::delete('/bulk-delete', [WorkOrderStepController::class, 'bulkDelete'])->name('bulk-delete');
                    Route::delete('/{id}', [WorkOrderStepController::class, 'destroy'])->name('destroy');     
               });

               // --- Task Dependencies ---
               
               
               Route::get('tasks/{task}/dependencies/datatable',  [WorkOrderTaskDependenciesController::class, 'datatable'])
               ->name('tasks.dependencies.datatable');
               Route::post('tasks/{task}/dependencies',           [WorkOrderTaskDependenciesController::class, 'store'])
               ->name('tasks.dependencies.store');
               Route::delete('dependencies/{dependency}',         [WorkOrderTaskDependenciesController::class, 'destroy'])
               ->name('tasks.dependencies.destroy');

               // --- Task Time Logs ---
               Route::get('tasks/{task}/time-logs/datatable',     [WorkOrderTaskTimeLogsController::class, 'datatable'])
               ->name('tasks.timelogs.datatable');
               Route::post('tasks/{task}/time-logs',              [WorkOrderTaskTimeLogsController::class, 'store'])
               ->name('tasks.timelogs.store');
               Route::put('time-logs/{log}',                      [WorkOrderTaskTimeLogsController::class, 'update'])
               ->name('tasks.timelogs.update');
               Route::delete('time-logs/{log}',                   [WorkOrderTaskTimeLogsController::class, 'destroy'])
               ->name('tasks.timelogs.destroy');

               // --- Materials lifecycle (Issue / Return) ---
               Route::post('materials/{material}/issue',          [WorkOrderMaterialsLifecycleController::class, 'issue'])
               ->name('materials.issue');
               Route::post('materials/{material}/return',         [WorkOrderMaterialsLifecycleController::class, 'return'])
               ->name('materials.return');
               // -----------------------------------------------      
               Route::prefix('cost-types')
               ->name('cost_types.')
               ->group(function () {
                    // For a type picker (Select2)
                    Route::get('/select2', [WorkOrderCostTypeController::class,'select2'])->name('select2');
                    Route::get('/',                [WorkOrderCostTypeController::class, 'index'])->name('index');
                    Route::get('/datatable',       [WorkOrderCostTypeController::class, 'datatable'])->name('datatable');
                    Route::post('/',               [WorkOrderCostTypeController::class, 'store'])->name('store');
                    Route::put('/{type}',          [WorkOrderCostTypeController::class, 'update'])->name('update');
                    Route::delete('/{type}',       [WorkOrderCostTypeController::class, 'destroy'])->name('destroy');
                    Route::get('/select2',         [WorkOrderCostTypeController::class, 'select2'])->name('select2'); // for pickers
               });

               // Tasks datatable + create under a specific work order
               Route::get('{workOrder}/tasks/datatable', [WorkOrderTaskController::class, 'datatable'])
               ->name('tasks.datatable');
               Route::post('{workOrder}/tasks', [WorkOrderTaskController::class, 'store'])
               ->name('tasks.store');

               // Per-task update/destroy/actions (task id only)
               Route::put   ('tasks/{task}',          [WorkOrderTaskController::class, 'update'])   ->name('tasks.update');
               Route::delete('tasks/{task}',          [WorkOrderTaskController::class, 'destroy'])  ->name('tasks.destroy');
               Route::post  ('tasks/{task}/start',    [WorkOrderTaskController::class, 'start'])    ->name('tasks.start');
               Route::post  ('tasks/{task}/stop',     [WorkOrderTaskController::class, 'stop'])     ->name('tasks.stop');
               Route::post  ('tasks/{task}/complete', [WorkOrderTaskController::class, 'complete']) ->name('tasks.complete');

               // routes/web.php
               Route::prefix('/{workOrder}/cost-lines')->name('cost_lines.')->group(function () {
                    Route::get('/datatable', [WorkOrderCostLineController::class,'datatable'])->name('datatable');
                    Route::post('/',           [WorkOrderCostLineController::class,'store'])->name('store');
                    Route::put('/{line}',     [WorkOrderCostLineController::class,'update'])->name('update');
                    Route::delete('/{line}',  [WorkOrderCostLineController::class,'destroy'])->name('destroy');
               });
               
               

               // Header
               Route::get('/',              [\Modules\Production\Http\Controllers\WorkOrderController::class, 'index'])->name('index');
               Route::get('/datatable',     [\Modules\Production\Http\Controllers\WorkOrderController::class, 'datatable'])->name('datatable');
               Route::get('/create',        [\Modules\Production\Http\Controllers\WorkOrderController::class, 'create'])->name('create');
               Route::post('/',             [\Modules\Production\Http\Controllers\WorkOrderController::class, 'store'])->name('store');
               Route::get('/{wo}',          [\Modules\Production\Http\Controllers\WorkOrderController::class, 'show'])->name('show');
               Route::put('/{line}',     [WorkOrderCostLineController::class,'update'])->name('update');
                    Route::delete('/{line}',  [WorkOrderCostLineController::class,'destroy'])->name('destroy');

               // Lifecycle
               Route::post('/{wo}/release',  [\Modules\Production\Http\Controllers\WorkOrderController::class, 'release'])->name('release');
               Route::post('/{wo}/start',    [\Modules\Production\Http\Controllers\WorkOrderController::class, 'start'])->name('start');
               Route::post('/{wo}/complete', [\Modules\Production\Http\Controllers\WorkOrderController::class, 'complete'])->name('complete');
               Route::post('/{wo}/close',    [\Modules\Production\Http\Controllers\WorkOrderController::class, 'close'])->name('close');

               // Materials
               Route::get('/{wo}/materials/datatable', [\Modules\Production\Http\Controllers\WorkOrderMaterialController::class, 'datatable'])
                    ->name('materials.datatable');

               // Steps
               Route::get('/{wo}/routings/steps/datatable', [\Modules\Production\Http\Controllers\WorkOrderStepController::class, 'datatable'])
                    ->name('routes.steps.datatable');
               Route::post('/steps/{step}/start',   [\Modules\Production\Http\Controllers\WorkOrderStepController::class, 'start'])->name('steps.start');
               Route::post('/steps/{step}/finish',  [\Modules\Production\Http\Controllers\WorkOrderStepController::class, 'finish'])->name('steps.finish');

               // Extra Costs (labour/logistics/fuel/etc.)
               Route::get('/{wo}/costs/datatable', [\Modules\Production\Http\Controllers\WorkOrderCostLineController::class, 'datatable'])
                    ->name('costs.datatable');
               Route::post('/{wo}/costs',          [\Modules\Production\Http\Controllers\WorkOrderCostLineController::class, 'store'])->name('costs.store');
               Route::put('/costs/{line}',         [\Modules\Production\Http\Controllers\WorkOrderCostLineController::class, 'update'])->name('costs.update');
               Route::delete('/costs/{line}',      [\Modules\Production\Http\Controllers\WorkOrderCostLineController::class, 'destroy'])->name('costs.destroy');                  
                              
                         });

          });

     /** Projects & Assets */
     Route::resource('projects', ProjectController::class);
     Route::resource('maintenance', MaintenanceController::class);
     Route::resource('assets', AssetController::class);

     /** Reports */
     Route::prefix('reports')->name('reports.')->group(function () {
          Route::get('sales', [SalesReportController::class, 'index'])->name('sales.index');
          Route::get('inventory', [InventoryReportController::class, 'index'])->name('inventory.index');
          Route::get('finance', [FinanceReportController::class, 'index'])->name('finance.index');
          Route::get('hr', [HRReportController::class, 'index'])->name('hr.index');
          Route::get('projects', [ProjectReportController::class, 'index'])->name('projects.index');
     });

    
    // Inventory routes
     Route::prefix('inventory')
          ->name('inventory.') 
          ->group(function () {
          
          Route::get('warehouses/stock', [WarehouseController::class, 'stock'])->name('warehouses.stock.index');
          Route::get('warehouses/movements', [WarehouseController::class, 'movements'])->name('warehouses.movements.index');


          Route::get('/stores/{store}/shelves', [LocationController::class, 'shelvesByStore']);

          
          Route::get('/dashboard', [ProductController::class, 'index'])
          ->name('dashboard'); 

     
          
          Route::prefix('raw-materials')
               ->name('raw-materials.')
               ->group(function () {
               Route::get('/', [ProductController::class, 'raw_materials'])
                    ->name('list'); 

               Route::get('/attributes', [ProductController::class, 'raw_materials_attributes'])
                    ->name('attributes');  

               Route::prefix('categories')
                    ->name('categories.')
                    ->group(function () {
                         Route::get('/', [ProductController::class, 'raw_materials_categories'])
                         ->name('list'); 

                         Route::get('/metrics', [ProductController::class, 'raw_materials_metrics'])
                         ->name('metrics'); 

                         Route::get('/datatable', [ProductController::class, 'raw_materials_categories_datatable'])
                         ->name('datatable'); 

                         Route::put('/', [ProductController::class, 'update_raw_materials_categories'])
                         ->name('update'); 

                         Route::post('/', [ProductController::class, 'store_raw_materials_categories'])
                         ->name('store'); 
               });

               Route::get('/datatable', [ProductController::class, 'raw_materials_datatable'])
                    ->name('datatable'); 

               // ✅ DeRfine the correct metrics route separately
               Route::get('/metrics', [ProductController::class, 'get_raw_materials_metrics'])
                    ->name('raw-materials.metrics'); 

               // ✅ Define the correct metrics route separately
               Route::post('/', [ProductController::class, 'store_raw_material'])
                    ->name('raw-materials.store'); 
          });

          Route::prefix('products')
               ->name('products.')
               ->group(function () {               
               Route::prefix('variants')->name('variants.')->group(function () {
                    Route::get('/', [ProductController::class, 'productVariantsIndex'])->name('index');
                    Route::get('/datatable', [ProductController::class, 'productVariantsDatatable'])->name('datatable');
                    Route::post('/', [ProductController::class, 'storeProductVariant'])->name('store');
                    Route::get('/{id}', [ProductController::class, 'editProductVariant'])->name('edit');
                    Route::put('/{id}', [ProductController::class, 'updateProductVariant'])->name('update');
                    Route::delete('/{id}', [ProductController::class, 'destroyProductVariant'])->name('destroy');
                    Route::post('/bulk-delete', [ProductController::class, 'bulkDeleteProductVariants'])->name('bulk-delete');
                });

               Route::prefix('attributes')->name('attributes.')->group(function () {
                    Route::get('/by-product/{productId}', [ProductController::class, 'getAttributesByProduct'])->name('by-products');
                    Route::prefix('types')->name('types.')->group(function () {
                         Route::get('/', [ProductController::class, 'productAttributeTypesIndex'])->name('index');
                         Route::post('/', [ProductController::class, 'storeProductAttributeType'])->name('store');
                         Route::put('/{id}', [ProductController::class, 'updateProductAttributeType'])->name('update');
                         Route::delete('/{id}', [ProductController::class, 'destroyProductAttributeType'])->name('destroy');
                         Route::get('/datatable', [ProductController::class, 'productAttributeTypesDatatable'])->name('datatable');
                    });
                    
                    Route::prefix('values')->name('values.')->group(function () {
                         Route::get('/', [ProductController::class, 'productAttributeValuesIndex'])->name('index');
                         Route::get('/datatable', [ProductController::class, 'productAttributeValuesDatatable'])->name('datatable');
                         Route::get('{id}/edit', [ProductController::class, 'editProductAttributeValue'])->name('edit');
                         Route::post('/', [ProductController::class, 'storeProductAttributeValue'])->name('store');
                         Route::put('/{id}', [ProductController::class, 'updateProductAttributeValue'])->name('update');
                         Route::delete('/{id}', [ProductController::class, 'destroyProductAttributeValue'])->name('destroy');
                         Route::post('/bulk-delete', [ProductController::class, 'bulkDeleteProductAttributeValues'])->name('bulk-delete');
                    });

                    Route::get('/', [ProductController::class, 'productAttributesIndex'])->name('index');
                    Route::post('/', [ProductController::class, 'storeProductAttribute'])->name('store');
                    Route::put('/{id}', [ProductController::class, 'updateProductAttribute'])->name('update');
                    Route::delete('/{id}', [ProductController::class, 'destroyProductAttribute'])->name('destroy');
                    Route::get('/datatable', [ProductController::class, 'productAttributesDatatable'])->name('datatable');
                    Route::post('bulk-delete', [ProductController::class, 'bulkDeleteProductAttribute'])->name('bulk-delete');
               });

               Route::prefix('brands')
               ->name('brands.')
               ->group(function () {
                    Route::get('/', [BrandController::class, 'index'])
                    ->name('list');

                    Route::get('/metrics', [BrandController::class, 'metrics'])
                    ->name('metrics');

                    Route::get('/datatable', [BrandController::class, 'datatable'])
                    ->name('datatable');

                    Route::post('/store', [BrandController::class, 'store'])
                    ->name('store');

                    Route::put('/{brand}', [BrandController::class, 'update'])
                    ->name('update');

                    Route::delete('/{brand}', [BrandController::class, 'destroy'])
                    ->name('destroy');

                    Route::post('brands/bulk-delete', [BrandController::class, 'bulkDelete'])->name('bulk-delete');
               });

               Route::prefix('manufacturers')
               ->name('manufacturers.')
               ->group(function () {
                    Route::get('/', [ManufacturerController::class, 'index'])
                    ->name('list');

                    Route::get('/metrics', [ManufacturerController::class, 'metrics'])
                    ->name('metrics');

                    Route::get('/datatable', [ManufacturerController::class, 'datatable'])
                    ->name('datatable');

                    Route::post('/store', [ManufacturerController::class, 'store'])
                    ->name('store');

                    Route::delete('/{manufacturer_id}', [ManufacturerController::class, 'destroy'])
                    ->name('delete');

                    Route::put('/{manufacturer}', [ManufacturerController::class, 'update'])
                    ->name('update');

                    Route::post('/manufacturers/bulk-delete', [ManufacturerController::class, 'bulkDelete'])->name('bulk-delete');

               });

               Route::prefix('units')
               ->name('units.')
               ->group(function () {
                    Route::get('/', [UnitController::class, 'index'])
                    ->name('list');

                    Route::get('/metrics', [UnitController::class, 'metrics'])
                    ->name('metrics');

                    Route::get('/datatable', [UnitController::class, 'datatable'])
                    ->name('datatable');

                    Route::post('/store', [UnitController::class, 'store'])
                    ->name('store');

                    Route::delete('/{manufacturer_id}', [UnitController::class, 'destroy'])
                    ->name('delete');

                    Route::put('/{unit}', [UnitController::class, 'update'])
                    ->name('update');

                    Route::post('/units/bulk-delete', [UnitController::class, 'bulkDelete'])->name('bulk-delete');

               });

               Route::get(
                    '/{product}/attributes',
                    [ProductController::class, 'attributeMatrix']
                )->name('admin.inventory.products.attributes');
               Route::get('/', [ProductController::class, 'index'])->name('index');
               Route::get('/datatable', [ProductController::class, 'datatable'])->name('datatable');
               Route::get('/metrics', [ProductController::class, 'metrics'])
                    ->name('metrics');
               Route::post('/', [ProductController::class, 'store'])->name('store');
               Route::put('/{id}', [ProductController::class, 'update'])->name('update');
               Route::delete('/{id}', [ProductController::class, 'destroy'])->name('destroy');
               Route::post('/bulk-delete', [ProductController::class, 'bulkDelete'])->name('bulk-delete');
          });

          // routes/web.php  (or your module route file)
          Route::prefix('returns')
               ->name('returns.')
               ->group(function () {

               Route::controller(CustomerReturnController::class)
                    ->prefix('customer')
                    ->name('customer.')
                    ->group(function(){
                    Route::get('/',             'index')->name('index');
                    Route::get('datatable',     'datatable')->name('datatable');
                    Route::post('/',            'store')->name('store');
                    Route::get('{return}',      'edit')->name('edit');
                    Route::put('{return}',      'update')->name('update');
                    Route::post('{return}/approve','approve')->name('approve');
                    Route::post('{return}/post',   'post')->name('post');
               });

               Route::controller(SupplierReturnController::class)
               ->prefix('supplier')
               ->name('supplier.')
               ->group(function(){
                    Route::get('/',             'index')->name('index');
                    Route::get('datatable',     'datatable')->name('datatable');
                    Route::post('/',            'store')->name('store');
                    Route::get('{return}',      'edit')->name('edit');
                    Route::put('{return}',      'update')->name('update');
                    Route::post('{return}/approve','approve')->name('approve');
                    Route::post('{return}/post',   'post')->name('post');
               });
          });


          Route::prefix('/suppliers')->name('suppliers.')->group(function () {
               Route::prefix('/addresses')->name('addresses.')->group(function () {
                    Route::get('/', [SupplierController::class, 'suppliersAddressesIndex'])->name('index');
                    Route::get('/datatable', [SupplierController::class, 'suppliersAddressesDatatable'])->name('datatable');
                    Route::post('/store', [SupplierController::class, 'storeSuppliersAddress'])->name('store');
                    Route::put('/{id}', [SupplierController::class, 'updateSuppliersAddress'])->name('update');
                    Route::delete('/{id}', [SupplierController::class, 'deleteSuppliersAddress'])->name('destroy');
                    Route::post('/bulk-delete', [SupplierController::class, 'bulkDeleteSuppliersAddresses'])->name('bulk-delete');
                    Route::get('/metrics', [SupplierController::class, 'metrics'])->name('metrics');
               
                    // Excel export route
                    Route::get('/export/excel', [SupplierController::class, 'exportExcel'])->name('export.excel');

               
                    // Excel export route
                    Route::get('/export/excel', [SupplierController::class, 'exportExcel'])->name('export.excel');
               });

               Route::prefix('/contacts')->name('contacts.')->group(function () {
                    Route::get('/', [SupplierController::class, 'suppliersContactsIndex'])->name('index');
                    Route::get('/datatable', [SupplierController::class, 'suppliersContactsDatatable'])->name('datatable');
                    Route::post('/store', [SupplierController::class, 'storesuppliersContact'])->name('store');
                    Route::put('/{id}', [SupplierController::class, 'updatesuppliersContact'])->name('update');
                    Route::delete('/{id}', [SupplierController::class, 'deleteSuppliersContact'])->name('delete');
                    Route::post('/bulk-delete', [SupplierController::class, 'bulkDeletesuppliersContacts'])->name('bulk-delete');
                    Route::get('/metrics', [SupplierController::class, 'metrics'])->name('metrics');
               
                    // Excel export route
                    Route::get('/export/excel', [SupplierController::class, 'exportExcel'])->name('export.excel');
               });
     
               Route::get('/select2', [SupplierController::class, 'select2'])->name('select2');
               Route::get('/', [SupplierController::class, 'index'])->name('index');
               Route::get('/datatable', [SupplierController::class, 'datatable'])->name('datatable');
               Route::post('/store', [SupplierController::class, 'store'])->name('store');
               Route::put('/{id}', [SupplierController::class, 'update'])->name('update');
               Route::delete('/{id}', [SupplierController::class, 'destroy'])->name('destroy');
               Route::post('/bulk-delete', [SupplierController::class, 'bulkDelete'])->name('bulk-delete');
               Route::get('/metrics', [SupplierController::class, 'metrics'])->name('metrics');
          
               // Excel export route
               Route::get('/export/excel', [SupplierController::class, 'exportExcel'])->name('export.excel');
          });

          Route::prefix('/stock')
               ->name('stock.')
               ->controller(StockLevelController::class)
               ->group(function () {
                    Route::prefix('dashboard')->name('dashboard.')->group(function () {
                         # routes/web.php   (within the inventory group / auth middleware)
                         Route::get('/',                 [StockDashboardController::class,'index'])
                         ->name('index');

                         Route::get('/cards',           [StockDashboardController::class,'cards'])->name('cards');;
                         Route::get('/top-movers',      [StockDashboardController::class,'topMovers'])->name('top-movers');
                         Route::get('/low-stock',       [StockDashboardController::class,'lowStock'])->name('low-stock');;
                         Route::get('/aging-chart',     [StockDashboardController::class,'agingChart'])->name('aging-chart');;
                    });

                    Route::prefix('aging')->name('aging.')->group(function () {
                         /* aging */
                         Route::get('/',            [StockAgingController::class,'index'])
                              ->name('index');
                     
                         Route::get('datatable',  [StockAgingController::class,'datatable'])
                              ->name('datatable');
                     });

                    Route::prefix('/levels')->name('levels.')->group(function () {
                         Route::prefix('/low')->name('low.')->group(function () {
                              Route::get('/', [StockLevelController::class,'lowStockLevelsIndex'])
                                   ->name('index');
                         
                              Route::get('datatable', [StockLevelController::class,'lowStockLevelsDatatable'])
                                   ->name('datatable');
                         });

                         Route::get('/', 'index')->name('index');
                         Route::get('/datatable','datatable')->name('datatable');
                         Route::get('/totals', 'totals')->name('totals');
                    });

                    Route::prefix('/transfers')
                         ->name('transfers.')
                         //->middleware(['auth','permission:inventory.transfer'])
                         ->group(function () {
                              Route::get('/fetch-variants', [StockTransferController::class,'fetch_variants'])->name('fetch-variants');

                              Route::get('/', [StockTransferController::class,'index'])->name('index');
                              Route::get('datatable', [StockTransferController::class,'datatable'])->name('datatable');

                              Route::get('create', [StockTransferController::class,'create'])->name('create');
                              Route::post('/', [StockTransferController::class,'store'])->name('store');

                              Route::get('{transfer}/edit', [StockTransferController::class,'edit'])->name('edit');
                              Route::post('{transfer}/post', [StockTransferController::class,'post'])->name('post');
                              Route::delete('{transfer}',    [StockTransferController::class,'destroy'])->name('destroy');
                         });

                    });

          // Stock Entries routes
          Route::prefix('stock-entries')->name('stock_entries.')->group(function () {

               Route::prefix('transactions')->name('transactions.')->group(function () {
                     /* Ledger */
                    Route::get('/', [StockController::class,'stockTransactionsIndex'])->name('index');

                    Route::post('/', [StockController::class,'storeStockTransaction'])->name('store');

                    Route::get('/datatable', [StockController::class,'stockTransactionsDatatable'])->name('datatable');

                    Route::get('/bulk-delete', [StockController::class,'bulkDeleteStockTransactions'])->name('bulk-delete');

               });

               Route::prefix('lines')->name('lines.')->group(function () {
                    /* Ledger */
                   Route::get('/', [StockController::class,'stockEntryLinesIndex'])->name('index');

                   Route::get('/datatable', [StockController::class,'stockEntryLinesDatatable'])->name('datatable');
                   Route::get('/{line}', [StockController::class,'showStockEntryLine'])->name('show');
                   
                   Route::post('/store', [StockController::class, 'storeStockEntryLine'])->name('store');
                   Route::put('/{id}', [StockController::class, 'updateStockEntryLine'])->name('update');
                   Route::delete('/{id}', [StockController::class, 'deleteStockEntryLine'])->name('delete');
                   Route::post('/bulk-delete', [StockController::class, 'bulkDeleteStockEntryLine'])->name('bulk-delete');
                   Route::get('/metrics', [StockController::class, 'StockEntryLineMetrics'])->name('metrics');
              });

               /* Entry lines */
               // Route::get ('/export', [StockController::class,'stockEntryLinesDatatable'])->name('export');

               Route::get(
                    '/export',
                    [StockController::class, 'export']
                )->name('export');
                
               Route::get ('{entry}/lines/datatable', [StockController::class,'stockEntryLinesDatatable'])->name('line.datatable');

               Route::get('/{stock_entry}/lines', [StockController::class,'stockEntryLinesIndex'])->name('lines.fetch');
               Route::post('/{entry}/lines', [StockController::class,'lines.store']);

               Route::delete('/lines/{id}', [StockController::class,'destroyStockEntryLine']);

               Route::get('/', [StockController::class, 'index'])->name('index');
               Route::get('/datatable', [StockController::class, 'datatable'])->name('datatable');
               Route::post('/', [StockController::class, 'store'])->name('store');
               Route::get('/{id}', [StockController::class, 'show'])->name('edit');
               Route::put('/{id}', [StockController::class, 'update'])->name('update');
               Route::delete('/{id}', [StockController::class, 'destroy'])->name('destroy');
               Route::post('/bulk-delete', [StockController::class, 'bulkDelete'])->name('bulkdelete');
          });

          Route::prefix('stock-issues')->name('stock_issues.')->group(function(){

               Route::post('/{issue}/approve', [StockIssueController::class,'approve'])->name('approve');

               Route::get('/{issue}/lines/table', [StockIssueController::class,'linesDatatable'])->name('lines');

               Route::get('/fetch_variants', [StockIssueController::class,'fetch_variants'])->name('fetch_variants');
               Route::get('/', [StockIssueController::class,'index'])->name('index');
               Route::get('/datatable', [StockIssueController::class,'datatable'])->name('datatable');
               Route::post('/', [StockIssueController::class,'store'])->name('store');
               Route::post('/{issue}/post', [StockIssueController::class,'post'])->name('post');
               
               Route::get('/{issue}', [StockIssueController::class,'show'])->name('show');
           });
     });

});
