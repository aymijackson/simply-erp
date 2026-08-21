<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CeoDashboardController;
use App\Http\Controllers\CfoDashboardController;
use App\Http\Controllers\ControlCenterController;
use App\Http\Controllers\CooDashboardController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ERPController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\LocationBlockController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuditAnalyticsController;
use App\Http\Controllers\WorkflowController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\Admin\HelpController;
use App\Http\Controllers\StoreShelfController;
use App\Http\Controllers\SupplierAnalyticsController;
use App\Http\Controllers\NotificationController;

use Modules\CRM\Http\Controllers\CrmDashboardController;
use Modules\CRM\Http\Controllers\ActivityController;
use Modules\CRM\Http\Controllers\ActivityAnalyticsController;
use Modules\CRM\Http\Controllers\CrmDocsController;
use Modules\CRM\Http\Controllers\CustomersAnalyticsController;
use Modules\CRM\Http\Controllers\CustomerSegmentationPresetsController;
use Modules\CRM\Http\Controllers\CustomerSegmentationController;
use Modules\CRM\Http\Controllers\InteractionsController;
use Modules\CRM\Http\Controllers\InteractionAnalyticsController;
use Modules\CRM\Http\Controllers\LeadController;
use Modules\CRM\Http\Controllers\LeadAnalyticsController;
use Modules\CRM\Http\Controllers\NotesController;
use Modules\CRM\Http\Controllers\NotesAnalyticsController;
use Modules\CRM\Http\Controllers\OpportunityController;
use Modules\CRM\Http\Controllers\OpportunityAnalyticsController;
use Modules\CRM\Http\Controllers\SupportTicketsController;

use Modules\Document\Http\Controllers\DocumentController;
use Modules\Document\Http\Controllers\DocumentCategoryController;
use Modules\Document\Http\Controllers\DocumentLinkController;
use Modules\Document\Http\Controllers\DocumentTypeController;
use Modules\Document\Http\Controllers\DocumentVersionController;

    
use Modules\Finance\Http\Controllers\FixedAssets\AssetCapitalisationController;
use Modules\Finance\Http\Controllers\FixedAssets\AssetComponentController;
use Modules\Finance\Http\Controllers\FixedAssets\AssetMaintenanceController;
use Modules\Finance\Http\Controllers\FixedAssets\TransferController;
use Modules\Finance\Http\Controllers\FixedAssets\RevaluationController;
use Modules\Finance\Http\Controllers\FixedAssets\ImpairmentController;
use Modules\Finance\Http\Controllers\FixedAssets\WriteoffController;
use Modules\Finance\Http\Controllers\FixedAssets\FAReportsController;
use Modules\Finance\Http\Controllers\AccountMappingController;
use Modules\Finance\Http\Controllers\AccountTypeController;
use Modules\Finance\Http\Controllers\ArAgeingController;
use Modules\Finance\Http\Controllers\APAgingController;
use Modules\Finance\Http\Controllers\BalanceSheetController;
use Modules\Finance\Http\Controllers\BankAccountsController;
use Modules\Finance\Http\Controllers\BankTransactionsController;
use Modules\Finance\Http\Controllers\BankReconciliationAdjustmentController;
use Modules\Finance\Http\Controllers\BankReconciliationController;
use Modules\Finance\Http\Controllers\BankReconciliationImportController;
use Modules\Finance\Http\Controllers\BankReconciliationMatchController;
use Modules\Finance\Http\Controllers\BudgetController;
use Modules\Finance\Http\Controllers\BudgetReportController;
use Modules\Finance\Http\Controllers\CashFlowController;
use Modules\Finance\Http\Controllers\ChartOfAccountsController;
use Modules\Finance\Http\Controllers\CustomerStatementController;
use Modules\Finance\Http\Controllers\ExchangeRateController;
use Modules\Finance\Http\Controllers\ExpenseCategoriesController;
use Modules\Finance\Http\Controllers\ExpensesController;
use Modules\Finance\Http\Controllers\FinanceDataFlushController;
use Modules\Finance\Http\Controllers\FinanceDocsController;
use Modules\Finance\Http\Controllers\FinanceHealthCheckController;
use Modules\Finance\Http\Controllers\FinanceInitialisationController;
use Modules\Finance\Http\Controllers\FinanceSettingsController;
use Modules\Finance\Http\Controllers\FiscalPeriodController;
use Modules\Finance\Http\Controllers\FixedAssets\FixedAssetController;
use Modules\Finance\Http\Controllers\FixedAssets\FixedAssetCategoryController;
use Modules\Finance\Http\Controllers\FixedAssets\FixedAssetTransactionController;
use Modules\Finance\Http\Controllers\Tax\TaxRateController;
use Modules\Finance\Http\Controllers\Tax\TaxCodeController;
use Modules\Finance\Http\Controllers\PettyCashController;
use Modules\Finance\Http\Controllers\PettyCashReconciliationController;

use Modules\Finance\Http\Controllers\FixedAssets\DepreciationController;
use Modules\Finance\Http\Controllers\GeneralLedgerController;
use Modules\Finance\Http\Controllers\JournalEntriesController;
use Modules\Finance\Http\Controllers\LookupsController;
use Modules\Finance\Http\Controllers\ProfitLossController;
use Modules\Finance\Http\Controllers\SupplierBillsController;
use Modules\Finance\Http\Controllers\SupplierCreditsController;
use Modules\Finance\Http\Controllers\SupplierPaymentsController;
use Modules\Finance\Http\Controllers\SupplierStatementsController;
use Modules\Finance\Http\Controllers\TrialBalanceController;
use Modules\Finance\Http\Controllers\YearCloseController;

use Modules\HRM\Http\Controllers\EmployeeController;
use Modules\HRM\Http\Controllers\PayrollController;
use Modules\HRM\Http\Controllers\TrainingController;
use Modules\HRM\Http\Controllers\PerformanceController;
use Modules\HRM\Http\Controllers\HrLeaveTypeController;
use Modules\HRM\Http\Controllers\HrShiftController;
use Modules\HRM\Http\Controllers\HrContractController;
use Modules\HRM\Http\Controllers\HrRecruitmentController;
use Modules\HRM\Http\Controllers\HrPayrollRunController;

use Modules\Inventory\Http\Controllers\BrandController;
use Modules\Inventory\Http\Controllers\CustomerReturnController;
use Modules\Inventory\Http\Controllers\SupplierReturnController;
use Modules\Inventory\Http\Controllers\ManufacturerController;
use Modules\Inventory\Http\Controllers\ProductCategoryController;
use Modules\Inventory\Http\Controllers\ProductController;
use Modules\Inventory\Http\Controllers\StockAgingController;
use Modules\Inventory\Http\Controllers\StockEntryAnalyticsController;
use Modules\Inventory\Http\Controllers\StockController;
use Modules\Inventory\Http\Controllers\StockDashboardController;
use Modules\Inventory\Http\Controllers\StockIssueController;
use Modules\Inventory\Http\Controllers\StockLevelController;
use Modules\Inventory\Http\Controllers\StockLevelsDashboardController;
use Modules\Inventory\Http\Controllers\StockTransferController;
use Modules\Inventory\Http\Controllers\StockTransferDashboardController;
use \Modules\Inventory\Http\Controllers\StoreStockController;
use Modules\Inventory\Http\Controllers\UnitController;
use Modules\Inventory\Http\Controllers\InventoryWorkflowController;
use Modules\Inventory\Http\Controllers\InventoryFlushController;

use Modules\Procurement\Http\Controllers\GoodsReceiptController;
use Modules\Procurement\Http\Controllers\ProcurementGuideController;
use Modules\Procurement\Http\Controllers\PurchaseRequisitionController;
use Modules\Procurement\Http\Controllers\PurchaseOrderController;
use Modules\Procurement\Http\Controllers\RequestForQuotationController;
use Modules\Procurement\Http\Controllers\SupplierQuotationController;

use Modules\Production\Http\Controllers\BomController;
use Modules\Production\Http\Controllers\BomDeficitController;
use Modules\Production\Http\Controllers\BomDeficitTransferController;
use Modules\Production\Http\Controllers\BOMItemController;
use Modules\Production\Http\Controllers\WorkOrderController;     
use Modules\Production\Http\Controllers\RawMaterialController;
use Modules\Production\Http\Controllers\RoutingController;
use Modules\Production\Http\Controllers\RoutingStepController;
use Modules\Production\Http\Controllers\WorkOrderMaterialController;
use Modules\Production\Http\Controllers\WorkOrderMaterialsLifecycleController;
use Modules\Production\Http\Controllers\WorkOrderStepController;
use Modules\Production\Http\Controllers\WorkOrderCostTypeController;
use Modules\Production\Http\Controllers\WorkOrderCostLineController;
use Modules\Production\Http\Controllers\WorkOrderMaterialsController;
use Modules\Production\Http\Controllers\WorkOrderTaskDependenciesController;
use Modules\Production\Http\Controllers\WorkOrderTaskController;
use Modules\Production\Http\Controllers\WorkOrderTaskTimeLogsController;

use Modules\Projects\Http\Controllers\ProjectBudgetController;

use Modules\Projects\Http\Controllers\ProjectController;
use Modules\Projects\Http\Controllers\ProjectDocsController;
use Modules\Projects\Http\Controllers\ProjectCostController;

use Modules\Projects\Http\Controllers\ProjectInvoiceController;
use Modules\Projects\Http\Controllers\ProjectMilestoneController;
use Modules\Projects\Http\Controllers\ProjectProfitabilityDashboardController;
use Modules\Projects\Http\Controllers\ProjectTaskController;
use Modules\Projects\Http\Controllers\ProjectTimesheetController;

use Modules\Sales\Http\Controllers\PriceListController;
use Modules\Sales\Http\Controllers\PricingRuleController;
use Modules\Sales\Http\Controllers\SalesAnalyticsController;
use Modules\Sales\Http\Controllers\SalesController;
use Modules\Sales\Http\Controllers\SalesCreditNoteController;
use Modules\Sales\Http\Controllers\SalesDataFlushController;
use Modules\Sales\Http\Controllers\SalesDeliveryController;
use Modules\Sales\Http\Controllers\SalesInvoiceController;
use Modules\Sales\Http\Controllers\SalesOrderController;
use Modules\Sales\Http\Controllers\SalesQuoteController;
use Modules\Sales\Http\Controllers\SalesPaymentController;

use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ReportController;

require __DIR__.'/auth.php';

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('admin.control_center')
        : redirect()->route('login');
})->name('home');

Route::get('/states/by-country/{country_id}', [LocationController::class, 'getStates']);
Route::get('/cities/by-state/{state_id}', [LocationController::class, 'getCities']);
Route::get('/brands/by-manufacturer/{manufacturer_id}', [ProductController::class, 'brandsByManufacturer']);

Route::get('/documents/public-preview/{id}', [DocumentController::class, 'publicPreview'])
    ->name('documents.public-preview')
    ->middleware('signed');
    
// ERP Main Admin Panel
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function() {

    Route::get('search', [\App\Http\Controllers\GlobalSearchController::class, 'search'])->name('search');

    Route::get('companies/search', [LocationController::class, 'getCompanies'])->name('companies.search');
    Route::get('countries/search', [LocationController::class, 'searchCountries'])->name('countries.search');
    Route::get('states/search', [LocationController::class, 'searchStates'])->name('states.search');
    Route::get('cities/search/byState', [LocationController::class, 'searchCities'])->name('cities.search.by_state');

    
    Route::get('/control-center', [ControlCenterController::class,'index'])
    ->name('control_center');
    
    
    Route::prefix('documents')->name('documents.')->group(function () {
        Route::get('/', [DocumentController::class, 'index'])->name('index');
        Route::post('/', [DocumentController::class, 'store'])->name('store');
        Route::get('row-template', [DocumentController::class, 'rowTemplate'])
        ->name('row-template');
        Route::get('/{id}', [DocumentController::class, 'show'])->name('show');
        Route::put('/{id}', [DocumentController::class, 'update'])->name('update');
        Route::delete('/{id}', [DocumentController::class, 'destroy'])->name('destroy');
        Route::get('{document}/audit-data', 
            [DocumentController::class, 'auditData'])->name('audit-data');
        Route::get('/{id}/download', [DocumentController::class, 'download'])->name('download');
        Route::get('/{id}/preview', [DocumentController::class, 'preview'])->name('preview');
        Route::post('/{id}/versions', [DocumentVersionController::class, 'store'])->name('versions.store');
    });

    Route::prefix('document-categories')->name('document-categories.')->group(function () {
        Route::get('/', [DocumentCategoryController::class, 'index'])->name('index');
        Route::post('/', [DocumentCategoryController::class, 'store'])->name('store');
        Route::put('/{id}', [DocumentCategoryController::class, 'update'])->name('update');
        Route::delete('/{id}', [DocumentCategoryController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('document-types')->name('document-types.')->group(function () {
        Route::get('/', [DocumentTypeController::class, 'index'])->name('index');
        Route::post('/', [DocumentTypeController::class, 'store'])->name('store');
        Route::put('/{id}', [DocumentTypeController::class, 'update'])->name('update');
        Route::delete('/{id}', [DocumentTypeController::class, 'destroy'])->name('destroy');
    });
    
    Route::prefix('document-links')->name('document-links.')->group(function () {
        Route::post('/', [DocumentLinkController::class, 'store'])->name('store');
        Route::delete('/{id}', [DocumentLinkController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('help')->name('help.')->group(function () {
        Route::get('/sales-module', [HelpController::class, 'salesModule'])
            ->name('sales-module');
    
        Route::get('/sales-module/pdf', [HelpController::class, 'salesModulePdf'])
            ->name('sales-module.pdf');
    });

    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/datatable', [NotificationController::class, 'datatable'])->name('datatable');
        Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('unreadCount');
        Route::get('/latest-dropdown', [NotificationController::class, 'latestDropdown'])->name('latestDropdown');
        Route::get('/{id}', [NotificationController::class, 'show'])->name('show');
        Route::post('/{id}/read', [NotificationController::class, 'markRead'])->name('read');
        Route::post('/{id}/unread', [NotificationController::class, 'markUnread'])->name('unread');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllRead'])->name('markAllRead');
        Route::post('/bulk-delete', [NotificationController::class, 'bulkDelete'])->name('bulkDelete');
        Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('destroy');
    });

    // Settings (main)
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/',               [\App\Http\Controllers\SettingController::class, 'index'])->name('index');
        Route::get('/datatable',      [\App\Http\Controllers\SettingController::class, 'datatable'])->name('datatable');
    
        Route::post('/',              [\App\Http\Controllers\SettingController::class, 'store'])->name('store');
        Route::put('/{setting}',      [\App\Http\Controllers\SettingController::class, 'update'])->name('update');
        Route::delete('/{setting}',   [\App\Http\Controllers\SettingController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-delete',   [\App\Http\Controllers\SettingController::class, 'bulkDelete'])->name('bulk_delete');
    
        // file value upload
        Route::post('/upload',        [\App\Http\Controllers\SettingController::class, 'upload'])->name('upload');
    });

    Route::prefix('store_shelves')->name('store_shelves.')->group(function () {
        Route::get('/', [StoreShelfController::class, 'index'])->name('index');
        Route::get('list', [StoreShelfController::class, 'list'])->name('list');
        Route::post('/', [StoreShelfController::class, 'store'])->name('store');
        Route::get('{id}/edit', [StoreShelfController::class, 'edit'])->name('edit');
        Route::put('{id}', [StoreShelfController::class, 'update'])->name('update');
        Route::delete('{id}', [StoreShelfController::class, 'destroy'])->name('destroy');
        Route::post('bulk-delete', [StoreShelfController::class, 'bulkDelete'])->name('bulk-delete');
    });
    
    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('index');
        Route::get('/ceo', [CeoDashboardController::class, 'index'])->name('ceo');
        Route::get('/cfo', [CfoDashboardController::class, 'index'])->name('cfo');
        Route::get('/coo', [CooDashboardController::class, 'index'])->name('coo');
    });
    
     Route::prefix('workflows')->name('workflows.')->group(function () {
        Route::get('/', [WorkflowController::class, 'index'])->name('index');
        Route::get('/datatable', [WorkflowController::class, 'datatable'])->name('datatable');
        Route::get('/{id}', [WorkflowController::class, 'show'])->name('show');
        Route::get('/{id}/logs', [WorkflowController::class, 'logs'])->name('logs');
        Route::post('/', [WorkflowController::class, 'store'])->name('store');
        Route::put('/{id}', [WorkflowController::class, 'update'])->name('update');
        Route::post('/{id}/toggle', [WorkflowController::class, 'toggle'])->name('toggle');
        Route::post('/bulk-delete', [WorkflowController::class, 'bulkDelete'])->name('bulkDelete');
        Route::delete('/{id}', [WorkflowController::class, 'destroy'])->name('destroy');  
        
    });
    
    
    Route::get('/regions/{id}/subregions', [ERPController::class, 'getSubregions']);
    Route::get('cities/search', [ERPController::class, 'searchCities'])->name('cities.search');
    Route::get('/locations/{location}/rooms', [ERPController::class, 'getRoomsByLocation']);

    Route::prefix('profile')->name('profile.')->group(function () {

        Route::get('/', [AdminController::class, 'profile'])->name('index');
        Route::patch('/theme', [AdminController::class, 'updateTheme'])->name('theme');
        Route::get('/debug-permissions', [AdminController::class, 'debugPermissions'])->name('debug-permissions');

    });
    
    Route::prefix('supplier-analytics')->name('supplier_analytics.')->group(function () {
        Route::get('/', [SupplierAnalyticsController::class, 'index'])->name('index');
    
        Route::get('/kpis', [SupplierAnalyticsController::class, 'kpis'])->name('kpis');
        Route::get('/trend', [SupplierAnalyticsController::class, 'trend'])->name('trend');
        Route::get('/datatable', [SupplierAnalyticsController::class, 'datatable'])->name('datatable');
    
        // ✅ NEW
        Route::get('/products', [SupplierAnalyticsController::class, 'productsDatatable'])->name('products.datatable');
        Route::get('/reasons', [SupplierAnalyticsController::class, 'reasonsDatatable'])->name('reasons.datatable');
    
        Route::get('/supplier/{supplier}', [SupplierAnalyticsController::class, 'show'])->name('show');
    });
        
    Route::prefix('support')->name('support.')->group(function () {

        Route::get('/', [AdminController::class, 'profile'])->name('index');
        
    });
    
    Route::prefix('customers')->name('customers.')->group(function () {

        Route::get('/select2', [CustomerController::class, 'select2'])->name('select2');
        Route::get('/', [CustomerController::class, 'index'])->name('index');
        Route::post('/', [CustomerController::class, 'store'])->name('store');
        Route::get('/datatable', [CustomerController::class, 'datatable'])->name('datatable');
        Route::get('/{id}/edit', [CustomerController::class, 'edit'])->name('edit');
        Route::get('/{customer}', [CustomerController::class, 'show'])->name('show');
        Route::put('/{customer}', [CustomerController::class, 'update'])->name('update');
        Route::delete('/bulk-delete', [CustomerController::class, 'bulkDelete'])->name('bulk-delete');
        Route::delete('/{id}', [CustomerController::class, 'destroy'])->name('destroy');     

        Route::get('/{customer}/opportunities/datatable', [CustomerController::class, 'opportunitiesDatatable'])
            ->name('show.opportunities.datatable');
    
        Route::get('/{customer}/invoices/datatable', [CustomerController::class, 'invoicesDatatable'])
            ->name('show.invoices.datatable');
    
        Route::get('/{customer}/interactions/datatable', [CustomerController::class, 'interactionsDatatable'])
            ->name('show.interactions.datatable');
    
        Route::get('/{customer}/support-tickets/datatable', [CustomerController::class, 'supportTicketsDatatable'])
            ->name('show.support-tickets.datatable');
    });

    // Core Components Management
    Route::prefix('companies')->name('companies.')->group(function () {

          Route::get('/select2', [CompanyController::class, 'select2'])
                    ->name('select2');
                    
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
     
     Route::prefix('drivers')->name('drivers.')->group(function () {
        Route::get('/', [DriverController::class, 'index'])->name('index');
        Route::get('datatable', [DriverController::class, 'datatable'])->name('datatable');
        Route::post('/', [DriverController::class, 'store'])->name('store');
        Route::put('{driver}', [DriverController::class, 'update'])->name('update');
        Route::delete('/{driver}', [DriverController::class, 'destroy'])->name('destroy');
    
        Route::get('select2', [DriverController::class, 'select2'])->name('select2');
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
          
          
        Route::put('locations/{id}', [LocationController::class, 'update'])->name('locations.update');
        
        Route::put('blocks/{id}', [LocationController::class, 'updateBlock'])->name('blocks.update');
        Route::put('floors/{id}', [LocationController::class, 'updateFloor'])->name('floors.update');
        Route::put('rooms/{id}', [LocationController::class, 'updateRoom'])->name('rooms.update');
        Route::put('stores/{id}', [LocationController::class, 'updateStore'])->name('stores.update');
        Route::put('shelves/{id}', [LocationController::class, 'updateShelf'])->name('shelves.update');


          Route::post('/', [ERPController::class, 'storeLocation'])->name('store');
          Route::get('/{id}/edit', [ERPController::class, 'editLocation'])->name('edit');
          Route::put('/{id}', [ERPController::class, 'updateLocation'])->name('update');
          Route::post('/bulk-delete', [ERPController::class, 'bulkDeleteLocations'])->name('bulk-delete');  
          Route::delete('/{id}', [ERPController::class, 'destroyLocation'])->name('destroy');             
     });

    #   for the general location blocks
    Route::prefix('location-blocks')->name('location-blocks.')->group(function () {
        Route::get('/', [LocationBlockController::class, 'index'])->name('index');
        Route::get('datatable', [LocationBlockController::class, 'datatable'])->name('datatable');
        Route::post('', [LocationBlockController::class, 'store'])->name('store');
        Route::get('{id}/edit', [LocationBlockController::class, 'edit'])->name('edit');
        Route::put('{id}', [LocationBlockController::class, 'update'])->name('update');
        Route::delete('{id}', [LocationBlockController::class, 'destroy'])->name('admin.destroy');
        Route::post('bulk-delete', [LocationBlockController::class, 'bulkDelete'])->name('bulk-delete');
    });
    
    Route::prefix('location_blocks')->name('location_blocks.')->group(function () {
          Route::get('/', [ERPController::class, 'locationBlocksIndex'])->name('index');
          Route::post('/', [ERPController::class, 'storeLocationBlock'])->name('store');
          Route::get('/list', [ERPController::class, 'locationBlocksList'])->name('list');
          Route::get('/datatable', [ERPController::class, 'locationBlocksDatatable'])->name('datatable');
          Route::get('/{id}/edit', [ERPController::class, 'editLocationBlock'])->name('edit');
          Route::put('/{id}', [ERPController::class, 'updateLocationBlock'])->name('update');
          Route::post('/bulk-delete', [ERPController::class, 'bulkDeleteLocationBlock'])->name('bulk-delete');  
          Route::delete('/{id}', [ERPController::class, 'destroyLocationBlock'])->name('destroy');             
     });

    Route::prefix('location_floors')->name('location_floors.')->group(function () {
          Route::get('/', [ERPController::class, 'locationBlockFloorsIndex'])->name('index');
          Route::post('/', [ERPController::class, 'storeLocationBlockFloor'])->name('store');
          Route::get('/list', [ERPController::class, 'locationBlockFloorsList'])->name('list');
          Route::get('/datatable', [LocationController::class, 'locationBlockFloorsDatatable'])->name('datatable');
          Route::get('/{id}/edit', [ERPController::class, 'editLocationBlockFloor'])->name('edit');
          
          Route::get('/{id}/fetch', [LocationController::class, 'editFloor'])->name('fetch');
          
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
          Route::get('stock/available', [StoreStockController::class, 'available'])->name('stock.available');
          Route::get('fetch', [LocationController::class, 'fetchStores'])->name('fetch-stores');
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
    /** Audit **/
    
    Route::prefix('audit')->name('audit.')->group(function () {
        Route::get('/', [AuditLogController::class, 'index'])->middleware('can:core.audit.view')->name('index');
        Route::get('/list', [AuditLogController::class, 'list'])->middleware('can:core.audit.view')->name('list');
        Route::get('/export', [AuditLogController::class, 'export'])->middleware('can:core.audit.export')->name('export');
        Route::delete('/purge', [AuditLogController::class, 'purge'])->middleware('can:core.audit.purge')->name('purge');
    
        Route::get('/analytics', [AuditAnalyticsController::class, 'index'])->middleware('can:core.audit.view.analytics')->name('analytics');
        Route::get('/analytics/data', [AuditAnalyticsController::class, 'data'])->middleware('can:core.audit.view.analytics')->name('analytics.data');
    
        Route::get('/{id}', [AuditLogController::class, 'show'])->middleware('can:core.audit.view')->name('show');
    });


    /** Users */
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('select2', [UserController::class, 'select2'])
    ->name('select2');
        Route::get('/{id}/permissions-details', [UserController::class, 'permissionsDetails']);
        Route::delete('bulk-delete', [UserController::class, 'bulkDelete'])->name('bulkDelete');
        Route::get('list', [UserController::class, 'list'])->name('list');
    });
    Route::resource('users', UserController::class);
    
    Route::prefix('vehicles')->name('vehicles.')->group(function () {
        Route::get('/', [VehicleController::class, 'index'])->name('index');
        Route::get('datatable', [VehicleController::class, 'datatable'])->name('datatable');
        Route::post('/', [VehicleController::class, 'store'])->name('store');
        Route::put('/{vehicle}', [VehicleController::class, 'update'])->name('update');
        Route::delete('/{vehicle}', [VehicleController::class, 'destroy'])->name('vehicl.destroy');
    
        Route::get('select2', [VehicleController::class, 'select2'])->name('select2');
    });

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
        
        // Lookups (Select2)
        Route::get('lookups/suppliers', [SupplierBillsController::class,'suppliers']);
        Route::get('lookups/gl-accounts', [SupplierBillsController::class,'glAccounts']);
        Route::get('lookups/bank-accounts', [SupplierBillsController::class,'bankAccounts']);
        Route::get('lookups/currencies', [SupplierBillsController::class,'currencies']);
        Route::get('lookups/ap-control-accounts', [SupplierBillsController::class,'apControlAccounts']);
            
        Route::prefix('accounts')->name('accounts.')->group(function () {
            Route::prefix('mappings')->name('mappings.')->group(function () {
                Route::get('/', [AccountMappingController::class, 'index'])->name('index');
                Route::post('/', [AccountMappingController::class, 'upsert'])->name('upsert');
            });
        });
        
        Route::prefix('account-types')
        ->name('account_types.')
        ->group(function () {
    
            Route::get('/', [AccountTypeController::class, 'index'])->name('index');
            Route::get('/datatable', [AccountTypeController::class, 'datatable'])->name('datatable');
            Route::get('/{id}', [AccountTypeController::class, 'show'])->name('show');
    
            Route::post('/', [AccountTypeController::class, 'store'])->name('store');
            Route::put('/{id}', [AccountTypeController::class, 'update'])->name('update');
            Route::delete('/{id}', [AccountTypeController::class, 'destroy'])->name('destroy');
        });
    
        Route::prefix('ap')->name('ap.')->group(function () {
            Route::get('/', 
                [ArAgeingController::class, 'index']
            )->name('index');
            
            Route::get('ageing', 
                [ArAgeingController::class, 'index']
            )->name('ageing');
        });
        
        Route::prefix('ar')->name('ar.')->group(function () {
            Route::get('ageing',
                [ArAgeingController::class, 'index']
            )->name('ageing');
        });
        
        Route::prefix('bank-accounts')->name('bank_accounts.')->group(function () {
            
            Route::get('/', [BankAccountsController::class, 'index'])->name('index');
            Route::get('datatable', [BankAccountsController::class, 'datatable'])->name('datatable');
        
            Route::post('/', [BankAccountsController::class, 'store'])->name('store');
            Route::put('/{bankAccount}', [BankAccountsController::class, 'update'])->name('update');
        
            Route::delete('/{bankAccount}', [BankAccountsController::class, 'destroy'])->name('destroy');
            Route::post('bulk-delete', [BankAccountsController::class, 'bulkDelete'])->name('bulk_delete');
            Route::post('set-default', [BankAccountsController::class, 'setDefault'])->name('set_default');

              // Select2 helper (pick GL account)
            Route::get('gl-accounts', [BankAccountsController::class, 'glAccounts'])->name('gl_accounts');
            
            Route::get('/accounts-recevable-aging', 
                [ArAgeingController::class, 'index']
            )->name('accounts_recevable_aging');
        
        });
        
        Route::prefix('bank-reconciliations')->name('bank_reconciliations.')->group(function () {
            Route::get('/', [BankReconciliationController::class,'index'])->name('index');
            Route::get('create', [BankReconciliationController::class,'create'])->name('create');
            Route::post('/', [BankReconciliationController::class,'store'])->name('store');
            
            // DataTables endpoints
            Route::get('dt', [BankReconciliationController::class,'dt'])->name('dt');
            
             Route::post('{id}/import', [BankReconciliationImportController::class,'import'])->name('import');
             
            Route::get('{id}/statement-lines-dt', [BankReconciliationController::class,'statementLinesDt'])->name('statement_lines_dt');
            
            Route::get('{id}/suggestions', [BankReconciliationController::class,'suggestions'])->name('suggestions');
            
            Route::get('{id}', [BankReconciliationController::class,'show'])->name('show');
        
            Route::post('{id}/close', [BankReconciliationController::class,'close'])->name('close');
            Route::post('{id}/undo-close', [BankReconciliationController::class,'undoClose'])->name('undo_close');
        });
        
        Route::prefix('bank-statement-lines')->name('bank_statement_lines.')->group(function () {
            // Statement lines
           
            Route::post('{id}/match', [BankReconciliationMatchController::class,'match'])->name('match');
            Route::post('{id}/unmatch', [BankReconciliationMatchController::class,'unmatch'])->name('unmatch');
            Route::post('{id}/exclude', [BankReconciliationMatchController::class,'exclude'])->name('exclude');
            Route::post('{id}/adjustment', [BankReconciliationAdjustmentController::class,'createAdjustment'])->name('adjustment');
            Route::post('{id}/undo-exclude',[BankReconciliationMatchController::class, 'undoExclude'])->name('undo_exclude');

        });
        
        Route::prefix('bank-transactions')->name('bank_transactions.')->group(function () {
          Route::get('/', [BankTransactionsController::class, 'index'])->name('index');
          Route::get('datatable', [BankTransactionsController::class, 'datatable'])->name('datatable');
    
          Route::post('/', [BankTransactionsController::class, 'store'])->name('store');
          Route::put('/{txn}', [BankTransactionsController::class, 'update'])->name('update');
          Route::delete('/{txn}', [BankTransactionsController::class, 'destroy'])->name('destroy');
    
          Route::post('bulk-delete', [BankTransactionsController::class, 'bulkDelete'])->name('bulk_delete');
    
          Route::post('{txn}/post', [BankTransactionsController::class, 'post'])->name('post');
          Route::post('{txn}/unpost', [BankTransactionsController::class, 'unpost'])->name('unpost');
    
          // Lookups
          Route::get('bank-accounts', [BankTransactionsController::class, 'bankAccounts'])->name('bank_accounts');
          Route::get('gl-accounts', [BankTransactionsController::class, 'glAccounts'])->name('gl_accounts');
          Route::get('currencies', [LookupsController::class, 'currencies'])->name('currencies');
          Route::get('banks', [LookupsController::class, 'banks'])->name('banks');
        });

        Route::prefix('budgets')->name('budgets.')->group(function () {
            Route::get('/', [BudgetController::class,'index'])->name('budgets.index');
            Route::get('budgets-dt', [BudgetController::class,'dt'])->name('dt');
        
            Route::get('create', [BudgetController::class,'create'])->name('create');
            Route::post('budgets', [BudgetController::class,'store'])->name('budgets.store');
        
            Route::get('{id}/edit', [BudgetController::class,'edit'])->name('edit');
            Route::post('{id}/save-grid', [BudgetController::class,'saveGrid'])->name('save_grid');
        
            Route::post('{id}/approve', [BudgetController::class,'approve'])->name('approve');
            Route::post('{id}/lock', [BudgetController::class,'lock'])->name('lock');
        
            Route::post('{id}/add-account', [BudgetController::class,'addAccount'])->name('add_account');
            Route::post('{id}/remove-account', [BudgetController::class,'removeAccount'])->name('remove_account');
        
            Route::get('budget-vs-actual/{id}', [BudgetReportController::class,'budgetVsActual'])->name('report');
        });

        Route::prefix('chart-of-accounts')->name('chart_of_accounts.')->group(function () {

            // Chart of Accounts
            Route::get('/', [ChartOfAccountsController::class, 'index'])
                ->name('index');
        
            Route::get('datatable', [ChartOfAccountsController::class, 'datatable'])
                ->name('datatable');
        
            Route::post('/', [ChartOfAccountsController::class, 'store'])
                ->name('store');
        
            Route::put('/{account}', [ChartOfAccountsController::class, 'update'])
                ->name('update');
        
            Route::delete('/{account}', [ChartOfAccountsController::class, 'destroy'])
                ->name('destroy');
        
            Route::post('bulk-delete', [ChartOfAccountsController::class, 'bulkDelete'])
                ->name('bulk_delete');
        
            // Select2 helper for parent accounts
            Route::get('parents', [ChartOfAccountsController::class, 'parentOptions'])
                ->name('parents');
        });
        
        Route::prefix('docs')->name('docs.')->group(function () {
            Route::get('/', [FinanceDocsController::class, 'index'])->name('index');
            Route::get('pdf', [FinanceDocsController::class, 'pdf'])->name('pdf');
        });
        
        Route::prefix('exchange-rates')->name('exchange_rates.')->group(function () {
 
            Route::get('/',           [ExchangeRateController::class, 'index'])
                ->name('index')
                ->middleware('permission:finance.exchange_rates.view');
         
            Route::get('/datatable',  [ExchangeRateController::class, 'datatable'])
                ->name('datatable')
                ->middleware('permission:finance.exchange_rates.view');
         
            Route::get('/{exchangeRate}', [ExchangeRateController::class, 'show'])
                ->name('show')
                ->middleware('permission:finance.exchange_rates.view');
         
            Route::post('/',          [ExchangeRateController::class, 'store'])
                ->name('store')
                ->middleware('permission:finance.exchange_rates.create');
         
            Route::put('/{exchangeRate}', [ExchangeRateController::class, 'update'])
                ->name('update')
                ->middleware('permission:finance.exchange_rates.edit');
         
            Route::delete('/{exchangeRate}', [ExchangeRateController::class, 'destroy'])
                ->name('destroy')
                ->middleware('permission:finance.exchange_rates.delete');
         
            Route::post('/bulk-delete',  [ExchangeRateController::class, 'bulkDelete'])
                ->name('bulk_delete')
                ->middleware('permission:finance.exchange_rates.delete');
         
            Route::post('/{exchangeRate}/toggle-active', [ExchangeRateController::class, 'toggleActive'])
                ->name('toggle_active')
                ->middleware('permission:finance.exchange_rates.edit');
         
            // Lookup used by procurement / sales / journal entry forms
            Route::get('/lookup/latest-rate', [ExchangeRateController::class, 'latestRate'])
                ->name('lookup.latest_rate');
         
            Route::get('/lookup/active-pairs', [ExchangeRateController::class, 'activePairs'])
                ->name('lookup.active_pairs');
        });

        // ===== Balance Sheet =====
        Route::prefix('reports/balance-sheet')->name('reports.balance_sheets.')->group(function () {
            
            Route::get('/', [BalanceSheetController::class, 'index'])
                ->name('index')
                ->middleware('permission:finance.reports.balance_sheet.view');
           
    
            Route::get('data', [BalanceSheetController::class, 'data'])
                ->name('data')
                ->middleware('permission:finance.reports.balance_sheet.view');
                
        });
        
        
        Route::prefix('customer-statement')->name('customer_statements.')->group(function(){

            Route::get('/',
            [CustomerStatementController::class,'index'])->name('index');
            
            Route::get('rows',
            [CustomerStatementController::class,'rows'])->name('rows');
            
            Route::get('summary',
            [CustomerStatementController::class,'summary'])->name('summary');
            
            Route::get('customers',
            [CustomerStatementController::class,'customers'])->name('lookup.customers');
        
        });

        Route::prefix('data-flush')->name('data_flush.')->group(function () {
            Route::get('/', [\Modules\Finance\Http\Controllers\FinanceDataFlushController::class, 'index'])
                ->name('index');
        
            Route::post('preview', [\Modules\Finance\Http\Controllers\FinanceDataFlushController::class, 'preview'])
                ->name('preview');
        
            Route::post('run', [\Modules\Finance\Http\Controllers\FinanceDataFlushController::class, 'run'])
                ->name('run');
        });
            
        Route::prefix('expense-categories')->name('expense_categories.')->group(function () {
        
            // Expense Categories
            Route::get('/', [ExpenseCategoriesController::class,'index'])
                ->name('index');
        
            Route::get('datatable', [ExpenseCategoriesController::class,'datatable'])
                ->name('datatable');
        
            Route::post('/', [ExpenseCategoriesController::class,'store'])
                ->name('store');
        
            Route::put('{category}', [ExpenseCategoriesController::class,'update'])
                ->name('update');
        
            Route::delete('{category}', [ExpenseCategoriesController::class,'destroy'])
                ->name('destroy');
        
            Route::post('bulk-delete', [ExpenseCategoriesController::class,'bulkDelete'])
                ->name('bulk_delete');
        
            // Select2 GL accounts lookup
            Route::get('gl-accounts', [ExpenseCategoriesController::class,'glAccounts'])
                ->name('gl_accounts');
        });
        
        Route::prefix('expenses')->name('expenses.')->group(function () {
            
          Route::get('/', [ExpensesController::class, 'index'])->name('index');
          
          Route::get('datatable', [ExpensesController::class, 'datatable'])->name('datatable');
    
          Route::post('/', [ExpensesController::class, 'store'])->name('store');
          Route::put('/{expense}', [ExpensesController::class, 'update'])->name('update');
          Route::delete('/{expense}', [ExpensesController::class, 'destroy'])->name('destroy');

          Route::post('/{expense}/post', [ExpensesController::class, 'post'])->name('post');
          Route::post('/{expense}/void', [ExpensesController::class, 'void'])->name('void');
    
          // Select2 helpers
          Route::get('lookups/categories', [ExpensesController::class, 'select2Categories'])->name('categories');
          Route::get('lookups/bank-accounts', [ExpensesController::class, 'select2BankAccounts'])->name('bank_accounts');
          Route::get('lookups/gl-accounts', [ExpensesController::class, 'select2GLAccounts'])->name('gl_accounts');
          Route::get('lookups/currencies', [ExpensesController::class, 'select2Currencies'])->name('currencies');
          Route::get('lookups/payable-accounts', [ExpensesController::class, 'select2PayableAccounts'])->name('payables');
          Route::get('lookups/suppliers', [ExpensesController::class, 'select2Suppliers'])->name('suppliers');
          Route::get('lookups/tax-codes', [ExpensesController::class, 'select2TaxCodes'])->name('lookups.taxcodes');
        });
        
        
            
        Route::prefix('fixed-assets')->name('fixed_assets.')->group(function () {

            
        
            // Categories
            Route::prefix('categories')->name('categories.')->group(function () {
                Route::get('/', [FixedAssetCategoryController::class, 'index'])->name('index');
                Route::get('datatable', [FixedAssetCategoryController::class, 'datatable'])->name('datatable');
                Route::get('{id}/json', [FixedAssetCategoryController::class, 'json'])->name('json');
                Route::post('/', [FixedAssetCategoryController::class, 'store'])->name('store');
                Route::put('{id}', [FixedAssetCategoryController::class, 'update'])->name('update');
                Route::delete('{id}', [FixedAssetCategoryController::class, 'destroy'])->name('destroy');
            });
        
            Route::prefix('components')->name('components.')->group(function () {
                Route::get('/', [AssetComponentController::class,'index'])->name('index');
                Route::get('datatable', [AssetComponentController::class,'datatable'])->name('datatable');
                Route::post('/', [AssetComponentController::class,'store'])->name('store');
                Route::post('{id}/activate', [AssetComponentController::class,'activate'])->name('activate');
                Route::post('{id}/retire', [AssetComponentController::class,'retire'])->name('retire');
            });
            
        
            Route::prefix('capitalisations')->name('capitalisations.')->group(function () {
                Route::get('/', [AssetCapitalisationController::class,'index'])->name('index');
                Route::get('/datatable', [AssetCapitalisationController::class,'datatable'])->name('datatable');
                Route::post('/', [AssetCapitalisationController::class,'store'])->name('store');
                Route::post('{id}/convert', [AssetCapitalisationController::class,'convert'])->name('convert');
                Route::post('{id}/void', [AssetCapitalisationController::class,'void'])->name('void');
            });
            
            Route::prefix('depreciation')->name('depreciation.')->group(function () {
                Route::get('/', [DepreciationController::class, 'index'])->name('index');
                Route::post('/preview', [DepreciationController::class, 'preview'])->name('preview');
                Route::post('/run', [DepreciationController::class, 'run'])->name('run');
                Route::post('/{runId}/post', [DepreciationController::class, 'post'])->name('post');
                Route::post('/{runId}/void', [DepreciationController::class, 'void'])->name('void');
            });
            
            Route::prefix('impairments')->name('impairments.')->group(function () {
                Route::get('/', [ImpairmentController::class,'index'])->name('index');
                Route::get('/datatable', [ImpairmentController::class,'datatable'])->name('datatable');
                Route::post('/', [ImpairmentController::class,'store'])->name('store');
                Route::post('{id}/post', [ImpairmentController::class,'post'])->name('post');
                Route::post('{id}/void', [ImpairmentController::class,'void'])->name('void');
            });
             
            Route::prefix('maintenance')->name('maintenance.')->group(function () {
                Route::get('/', [AssetMaintenanceController::class,'index'])->name('index');
                Route::get('/datatable', [AssetMaintenanceController::class,'datatable'])->name('datatable');
                Route::post('/', [AssetMaintenanceController::class,'store'])->name('store');
                Route::post('{id}/post', [AssetMaintenanceController::class,'post'])->name('post');
                Route::post('{id}/void', [AssetMaintenanceController::class,'void'])->name('void');
            });

            Route::prefix('transactions')->name('transactions.')->group(function () {
                // Asset‑scoped (Acquisition + Disposal)
                Route::get('/{assetId}', [FixedAssetTransactionController::class, 'index'])->name('index');
                Route::post('/{assetId}', [FixedAssetTransactionController::class, 'store'])->name('store');
            
                // Transaction‑scoped actions
                Route::post('/{txnId}/post', [FixedAssetTransactionController::class, 'post'])->name('post');
                Route::post('/{txnId}/void', [FixedAssetTransactionController::class, 'void'])->name('void');
            });
            
            Route::prefix('transfers')->name('transfers.')->group(function () {
                Route::get('/', [TransferController::class,'index'])->name('index');
                Route::get('/datatable', [TransferController::class,'datatable'])->name('datatable');
                Route::post('/', [TransferController::class,'store'])->name('store');
                Route::post('{id}/post', [TransferController::class,'post'])->name('post');
                Route::post('{id}/void', [TransferController::class,'void'])->name('void');
            });
        
            Route::prefix('revaluations')->name('revaluations.')->group(function () {
                Route::get('/', [RevaluationController::class,'index'])->name('index');
                Route::get('/datatable', [RevaluationController::class,'datatable'])->name('datatable');
                Route::post('/', [RevaluationController::class,'store'])->name('store');
                Route::post('{id}/post', [RevaluationController::class,'post'])->name('post');
                Route::post('{id}/void', [RevaluationController::class,'void'])->name('void');
            });
        
            Route::prefix('writeoffs')->name('writeoffs.')->group(function () {
                Route::get('/', [WriteoffController::class,'index'])->name('index');
                Route::get('/datatable', [WriteoffController::class,'datatable'])->name('datatable');
                Route::post('/', [WriteoffController::class,'store'])->name('store');
                Route::post('{id}/post', [WriteoffController::class,'post'])->name('post');
                Route::post('{id}/void', [WriteoffController::class,'void'])->name('void');
            });
        
            Route::prefix('reports')->name('reports.')->group(function () {
                Route::get('/', [FAReportsController::class,'index'])->name('index');
            
                // Register Reports
                Route::prefix('register')->name('register.')->group(function () {
                    Route::get('/', [FAReportsController::class,'register'])->name('index');
                    Route::get('/pdf', [FAReportsController::class,'registerPdf'])->name('pdf');
                });
            
                // Depreciation Reports
                Route::prefix('depreciation')->name('depreciation.')->group(function () {
                    Route::get('/', [FAReportsController::class,'depreciation'])->name('index');
                    Route::get('/pdf', [FAReportsController::class,'depreciationPdf'])->name('pdf');
                });
            
                // Movements Reports
                Route::prefix('movements')->name('movements.')->group(function () {
                    Route::get('/', [FAReportsController::class,'movements'])->name('index');
                    Route::get('/pdf', [FAReportsController::class,'movementsPdf'])->name('pdf');
                });
            
                // Forecast Reports
                Route::prefix('forecast')->name('forecast.')->group(function () {
                    Route::get('/', [FAReportsController::class,'forecast'])->name('index');
                    Route::get('/pdf', [FAReportsController::class,'forecastPdf'])->name('pdf');
                });
            });
             
            // Assets
            Route::get('/', [FixedAssetController::class, 'index'])->name('index');
            Route::get('/datatable', [FixedAssetController::class, 'datatable'])->name('datatable');
            Route::get('/{id}/json', [FixedAssetController::class, 'json'])->name('json');
            Route::post('/', [FixedAssetController::class, 'store'])->name('store');
            Route::put('/{id}', [FixedAssetController::class, 'update'])->name('update');
            Route::delete('/{id}', [FixedAssetController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/activate', [FixedAssetController::class, 'activate'])->name('activate');
            Route::get('/{id}', [FixedAssetController::class, 'show'])->name('show');
        });
        
        Route::prefix('health-check')->name('health_check.')->middleware(['auth'])->group(function () {
            Route::get('/', [FinanceHealthCheckController::class, 'index'])
                ->name('index');
        
            Route::post('run', [FinanceHealthCheckController::class, 'run'])
                ->name('run');
        });
                
        Route::prefix('initialisation')->name('initialisation.')->middleware(['auth'])->group(function () {
            Route::get('/', [\Modules\Finance\Http\Controllers\FinanceInitialisationController::class, 'index'])
                ->name('index');
        
            Route::post('preview', [\Modules\Finance\Http\Controllers\FinanceInitialisationController::class, 'preview'])
                ->name('preview');
        
            Route::post('run', [\Modules\Finance\Http\Controllers\FinanceInitialisationController::class, 'run'])
                ->name('run');
        });

        Route::prefix('journal-entries')->name('journal_entries.')->group(function () {
              Route::get('/', [JournalEntriesController::class, 'index'])->name('index');
              Route::get('datatable', [JournalEntriesController::class, 'datatable'])->name('datatable');
        
              Route::post('/', [JournalEntriesController::class, 'store'])->name('store');
              
              Route::put('/{entry}', [JournalEntriesController::class, 'update'])->name('update');
              Route::delete('/{entry}', [JournalEntriesController::class, 'destroy'])->name('destroy');
        
              Route::post('/{entry}/post', [JournalEntriesController::class, 'post'])->name('post');
              Route::post('/{entry}/reverse', [JournalEntriesController::class, 'reverse'])->name('reverse');
              Route::post('/{entry}/void', [JournalEntriesController::class, 'void'])->name('void');
        
              Route::get('/{entry}/lines', [JournalEntriesController::class, 'lines'])->name('lines');
        
              // Lookups
              Route::get('accounts', [JournalEntriesController::class, 'glAccounts'])->name('accounts');
              Route::get('bank-accounts', [JournalEntriesController::class, 'bankAccounts'])->name('bank_accounts');
              Route::get('currencies', [JournalEntriesController::class, 'currencies'])->name('currencies');
              Route::get('/{id}', [JournalEntriesController::class,'show'])->name('show');
        });
        
        Route::prefix('lookups')->name('lookups.')->group(function () {
            Route::get('currencies', [LookupsController::class, 'currencies'])
          ->name('currencies');

            Route::get('banks', [LookupsController::class, 'banks'])
              ->name('banks');
              
        });
        
        Route::prefix('periods')->name('periods.')->group(function () {
            Route::get('/', [FiscalPeriodController::class, 'index'])
                ->name('index');
        
            Route::post('{id}/close', [FiscalPeriodController::class, 'close'])
                ->name('close');
        
            Route::post('{id}/reopen', [FiscalPeriodController::class, 'reopen'])
                ->name('reopen');
        });

        Route::prefix('petty-cash')->name('petty_cash.')->group(function () {
            Route::get('/accounts/select2', [PettyCashController::class, 'accountsSelect2'])->name('accounts.select2');
            Route::get('/payees/select2', [PettyCashController::class, 'payeesSelect2'])->name('payees.select2');
            Route::get('/accounts/select2', [PettyCashController::class, 'accountsSelect2'])->name('accounts.select2');
            Route::get('/payees/select2', [PettyCashController::class, 'payeesSelect2'])->name('payees.select2');
            Route::get('/', [PettyCashController::class, 'index'])->name('index');
        
            Route::get('/accounts/{id}/balance', [PettyCashController::class, 'getAccountBalance']);
            
            Route::get('/accounts/{id}', [PettyCashController::class, 'showAccount'])->name('accounts.show');
            Route::get('/accounts/{id}/edit', [PettyCashController::class, 'editAccount'])->name('accounts.edit');
            Route::put('/accounts/{id}', [PettyCashController::class, 'updateAccount'])->name('accounts.update');
            Route::delete('/accounts/{id}', [PettyCashController::class, 'destroyAccount'])->name('accounts.destroy');

            Route::get('/accounts', [PettyCashController::class, 'accounts'])->name('accounts');
            Route::post('/accounts', [PettyCashController::class, 'storeAccount'])->name('accounts.store');
        
            Route::prefix('reconciliations')
            ->name('reconciliations')
            ->group(function () {
                Route::get('/', [PettyCashReconciliationController::class, 'index']);
                Route::get('/account-snapshot/data', [PettyCashReconciliationController::class, 'accountSnapshot'])->name('.account_snapshot');
                Route::post('/', [PettyCashReconciliationController::class, 'store'])->name('.store');
                Route::get('/{id}/show', [PettyCashReconciliationController::class, 'show'])->name('.show');
                Route::get('/{id}/edit', [PettyCashReconciliationController::class, 'edit'])->name('.edit');
                Route::put('/{id}', [PettyCashReconciliationController::class, 'update'])->name('.update');
                Route::post('/{id}/submit', [PettyCashReconciliationController::class, 'submit'])->name('.submit');
                Route::post('/{id}/approve', [PettyCashReconciliationController::class, 'approve'])->name('.approve');
                Route::post('/{id}/reject', [PettyCashReconciliationController::class, 'reject'])->name('.reject');
                Route::delete('/{id}', [PettyCashReconciliationController::class, 'destroy'])->name('.destroy');
            });
            
            
            Route::post('/store', [PettyCashController::class, 'storeTransaction'])->name('store');
            Route::get('/{id}', [PettyCashController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [PettyCashController::class, 'edit'])->name('edit');
            Route::put('/{id}', [PettyCashController::class, 'updateTransaction'])->name('update');
            Route::post('/{id}/approve', [PettyCashController::class, 'approve'])->name('approve');
            Route::post('/{id}/reject', [PettyCashController::class, 'reject'])->name('reject');
            Route::post('/{id}/post', [PettyCashController::class, 'post'])->name('post');
            Route::get('/{id}/voucher', [PettyCashController::class, 'voucher'])->name('voucher');
            Route::delete('/{id}', [PettyCashController::class, 'destroy'])->name('destroy');
        

            
            Route::post('/reconciliations/{id}/post', [PettyCashController::class, 'postReconciliation'])->name('reconciliations.post');
        
            Route::get('/audit/trail', [PettyCashController::class, 'auditTrail'])->name('audit');
        });
        Route::prefix('reports/account-drilldown/')->name('reports.account_drilldown.')->group(function () {
            Route::get('{account}',
                        [ProfitLossController::class,'drilldown'])->name('reports.account.drilldown');
                        
        });

        Route::prefix('reports/ar-ageing')->name('reports.ar_ageing.')->group(function(){
            Route::get('/', [ArAgeingController::class, 'index'])
            ->name('ar_ageing');

            Route::get('datatable', [ArAgeingController::class, 'datatable'])
                ->name('datatable');
        
            Route::get('customer/{customerId}/invoices', [ArAgeingController::class, 'customerInvoices'])
                ->name('customer_invoices');
        
            // lookup (customer select2)
            Route::get('lookups/customers', [ArAgeingController::class, 'customers'])
                ->name('lookups.customers');
        });
        
         Route::prefix('reports/cash-flow')->name('reports.cash_flow.')->group(function () {
            Route::get('/', [CashFlowController::class, 'index'])
                ->name('index')
                ->middleware('permission:finance.reports.cash_flow.view');
        
            Route::get('run', [CashFlowController::class, 'run'])
                ->name('run')
                ->middleware('permission:finance.reports.cash_flow.view');
        
            // optional mappings
            Route::get('mappings', [CashFlowController::class, 'mappingsIndex'])
                ->name('mappings.index')
                ->middleware('permission:finance.reports.cash_flow.manage');

            Route::get('mappings/data', [CashFlowController::class, 'mappingsData'])
                ->name('mappings.data')
                ->middleware('permission:finance.reports.cash_flow.manage');

            Route::post('mappings', [CashFlowController::class, 'mappingsStore'])
                ->name('mappings.store')
                ->middleware('permission:finance.reports.cash_flow.manage');
        
            Route::delete('mappings/{id}', [CashFlowController::class, 'mappingsDelete'])
                ->name('delete')
                ->middleware('permission:finance.reports.cash_flow.manage');
        
            // lookups
            Route::get('lookups/gl', [CashFlowController::class, 'lookupGl'])
                ->name('lookups.gl')
                ->middleware('permission:finance.reports.cash_flow.manage');
        });
        
        Route::prefix('reports/general-ledger')->name('reports.general_ledger.')->group(function () {
            Route::get('/', [GeneralLedgerController::class, 'index'])
                ->name('index')
                ->middleware('permission:finance.reports.general_ledger.view');
    
            Route::get('data', [GeneralLedgerController::class, 'data'])
                ->name('data')
                ->middleware('permission:finance.reports.general_ledger.view');
    
            Route::get('lookups/accounts', [GeneralLedgerController::class, 'accountsLookup'])
                ->name('accounts')
                ->middleware('permission:finance.reports.general_ledger.view');
                
            Route::get('pdf', [GeneralLedgerController::class, 'pdf'])
                ->name('pdf')->middleware('permission:finance.reports.general_ledger.view');;

            Route::get('excel', [GeneralLedgerController::class, 'excel'])
                ->name('excel')->middleware('permission:finance.reports.general_ledger.view');;
        });
    
        // ===== Trial Balance =====
        Route::prefix('reports/trial-balance')->name('reports.trial_balance.')->group(function () {
            Route::get('/', [TrialBalanceController::class, 'index'])
                ->name('index')
                ->middleware('permission:finance.reports.trial_balance.view');
    
            Route::get('datatable', [TrialBalanceController::class, 'datatable'])
                ->name('datatable')
                ->middleware('permission:finance.reports.trial_balance.view');
            
            Route::get('excel', [TrialBalanceController::class, 'excel'])
                ->name('excel')
                ->middleware('permission:finance.reports.trial_balance.view');
                
            Route::get('lookups/accounts', [TrialBalanceController::class, 'accounts'])
                ->name('accounts')
                ->middleware('permission:finance.reports.trial_balance.view');
                
            Route::get('pdf', [TrialBalanceController::class, 'pdf'])
                ->name('pdf')
                ->middleware('permission:finance.reports.trial_balance.view');
        });
        
        // ===== Profit Loss =====
        Route::prefix('reports/profit-loss')->name('reports.profit_loss.')->group(function () {
            
            Route::get('/', [ProfitLossController::class, 'index'])
                ->name('index')
                ->middleware('permission:finance.reports.profit_loss.view');
           
    
            Route::get('data', [ProfitLossController::class, 'data'])
                ->name('data')
                ->middleware('permission:finance.reports.profit_loss.view');
                
            Route::get('excel', [ProfitLossController::class, 'excel'])
                ->name('excel')
                ->middleware('permission:finance.reports.profit_loss.view');
                
            Route::get('pdf', [ProfitLossController::class, 'pdf'])
                ->name('pdf')
                ->middleware('permission:finance.reports.profit_loss.view');
        });
            
            
        Route::prefix('supplier-bills')->name('supplier_bills.')->group(function () {
            Route::get('/', [SupplierBillsController::class,'index'])->name('index');
            Route::get('datatable', [SupplierBillsController::class,'datatable'])->name('datatable');
            Route::post('/', [SupplierBillsController::class,'store'])->name('store');
            Route::put('{id}', [SupplierBillsController::class,'update'])->name('update');
            Route::delete('{id}', [SupplierBillsController::class,'destroy'])->name('destroy');
        
            Route::get('lookups/source-records',[SupplierBillsController::class,'lookupSourceRecords']);

            Route::get('load-source',[SupplierBillsController::class,'loadSource']);
            
            Route::get('{id}/lines', [SupplierBillsController::class,'lines'])->name('lines');
            Route::post('{id}/post', [SupplierBillsController::class,'post'])->name('post');
            Route::post('{id}/void', [SupplierBillsController::class,'void'])->name('void');

            Route::get('{bill}/view', [SupplierBillsController::class, 'showPage'])
                ->name('show_page')->middleware('permission:finance.ap.view');

            Route::get('{bill}', [SupplierBillsController::class, 'show']);
            Route::get('{bill}/pdf', [SupplierBillsController::class, 'pdf']);
        
        });

        /*
        |--------------------------------------------------------------------------
        | Supplier Credits (Vendor Credit Notes)
        |--------------------------------------------------------------------------
        */
        Route::prefix('supplier-credits')->name('supplier_credits.')->group(function () {
            Route::get('/', [SupplierCreditsController::class, 'index'])
                ->name('index')->middleware('permission:finance.ap.credits.view');

            Route::get('/datatable', [SupplierCreditsController::class, 'datatable'])
                ->name('datatable')->middleware('permission:finance.ap.credits.view');

            Route::post('/', [SupplierCreditsController::class, 'store'])
                ->name('store')->middleware('permission:finance.ap.credits.create');

            Route::put('/{id}', [SupplierCreditsController::class, 'update'])
                ->name('update')->middleware('permission:finance.ap.credits.edit');

            Route::delete('/{id}', [SupplierCreditsController::class, 'destroy'])
                ->name('destroy')->middleware('permission:finance.ap.credits.delete');

            Route::get('/{id}/lines', [SupplierCreditsController::class, 'lines'])
                ->name('lines')->middleware('permission:finance.ap.credits.view');

            Route::post('/{id}/post', [SupplierCreditsController::class, 'post'])
                ->name('post')->middleware('permission:finance.ap.credits.post');

            Route::post('/{id}/void', [SupplierCreditsController::class, 'void'])
                ->name('void')->middleware('permission:finance.ap.credits.void');

            // Lookups (Select2)
            Route::get('/lookups/suppliers', [SupplierCreditsController::class, 'suppliers'])
                ->name('lookups.suppliers')->middleware('permission:finance.ap.credits.view');

            Route::get('/lookups/ap-control-accounts', [SupplierCreditsController::class, 'apControlAccounts'])
                ->name('lookups.ap_control_accounts')->middleware('permission:finance.ap.credits.view');

            Route::get('/lookups/currencies', [SupplierCreditsController::class, 'currencies'])
                ->name('lookups.currencies')->middleware('permission:finance.ap.credits.view');
                // lookups
             Route::get('lookups/open-supplier-bills', [SupplierCreditsController::class,'openBills']);
             
             Route::get('lookups/tax-codes', [SupplierCreditsController::class, 'taxCodes']);
             
            Route::get('lookups/tax-rates', [SupplierCreditsController::class, 'taxRates']);
        });

        /*
        |--------------------------------------------------------------------------
        | AP Aging
        |--------------------------------------------------------------------------
        */
        Route::prefix('ap-aging')->name('ap_aging.')->group(function () {
            Route::get('/', [APAgingController::class, 'index'])
                ->name('index')->middleware('permission:finance.ap.aging.view');

            // datatable or api endpoint used by the report page
            Route::get('/datatable', [APAgingController::class, 'datatable'])
                ->name('datatable')->middleware('permission:finance.ap.aging.view');
        });


        Route::prefix('supplier-payments')->name('supplier_payments.')->middleware(['auth'])->group(function () {
              Route::get('/', [SupplierPaymentsController::class,'index']);
              Route::get('datatable', [SupplierPaymentsController::class,'datatable']);
              Route::get('{id}/allocations', [SupplierPaymentsController::class,'allocations']);

              Route::post('supplier-payments', [SupplierPaymentsController::class,'store']);
              Route::put('{id}', [SupplierPaymentsController::class,'update']);
              Route::delete('{id}', [SupplierPaymentsController::class,'destroy']);

              Route::post('{id}/post', [SupplierPaymentsController::class,'post']);
              Route::post('{id}/void', [SupplierPaymentsController::class,'void']);

              // lookups
              Route::get('lookups/suppliers', [\Modules\Finance\Http\Controllers\FinanceLookupsController::class,'suppliers']);
              Route::get('lookups/ap-control-accounts', [\Modules\Finance\Http\Controllers\FinanceLookupsController::class,'apControlAccounts']);
              Route::get('lookups/open-supplier-bills', [\Modules\Finance\Http\Controllers\FinanceLookupsController::class,'openSupplierBills']);

              // New, named real detail page (the rest of this group is pre-existing and
              // intentionally left as-is - see finance_supplier_payments show page work).
              Route::get('{id}', [SupplierPaymentsController::class, 'show'])
                  ->name('show')->middleware('permission:finance.payments.view');
        });
        
        Route::prefix('reports/supplier-statements')->name('reports.supplier_statements.')->group(function () {
            
            // Supplier Statements
            Route::get('/', [SupplierStatementsController::class, 'index'])
              ->name('index');
        
            Route::get('data', [SupplierStatementsController::class, 'data'])
              ->name('data');
        
            // Select2 lookup (suppliers.name only)
            Route::get('lookups/suppliers', [SupplierStatementsController::class, 'suppliers'])
              ->name('lookups.suppliers');
        
            // Optional printable view
            Route::get('print', [SupplierStatementsController::class, 'print'])
              ->name('print');
      
        });
    
        Route::prefix('tax')->name('tax.')->group(function () {

            Route::prefix('rates')->name('rates.')->group(function () {
        
                // Tax Rates
                Route::get('/', [TaxRateController::class, 'index'])->name('index');
                Route::get('datatable', [TaxRateController::class, 'datatable'])->name('datatable');
                Route::get('{id}/json', [TaxRateController::class, 'json'])->name('json');
                Route::post('/', [TaxRateController::class, 'store'])->name('store');
                Route::put('{id}', [TaxRateController::class, 'update'])->name('update');
                Route::delete('{id}', [TaxRateController::class, 'destroy'])->name('destroy');
                
            });
        
            Route::prefix('codes')->name('codes.')->group(function () {
                // Tax Codes
                Route::get('/', [TaxCodeController::class, 'index'])->name('index');
                Route::get('datatable', [TaxCodeController::class, 'datatable'])->name('datatable');
                Route::get('{id}/json', [TaxCodeController::class, 'json'])->name('json');
                Route::post('/', [TaxCodeController::class, 'store'])->name('store');
                Route::put('{id}', [TaxCodeController::class, 'update'])->name('update');
                Route::delete('{id}', [TaxCodeController::class, 'destroy'])->name('destroy');
            });
        
        });
        
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [FinanceSettingsController::class, 'index'])
              ->name('index');
            
            Route::post('/', [\Modules\Finance\Http\Controllers\FinanceSettingsController::class, 'save'])
              ->name('save');
  
        });
    
        Route::prefix('year-close')->name('year_close.')->group(function () {
            Route::get('/', [YearCloseController::class, 'index'])
                ->name('index');
            
            Route::post('run', [YearCloseController::class, 'run'])
                ->name('run');
                
        });
     });

     /** Sales */
    Route::prefix('sales')->name('sales.')->group(function () {

        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('/', [SalesAnalyticsController::class,'index'])->name('index');
            Route::get('summary', [SalesAnalyticsController::class,'summary'])->name('summary');
            Route::get('trends', [SalesAnalyticsController::class,'trends'])->name('trends');
            Route::get('ar-aging', [SalesAnalyticsController::class,'arAging'])->name('ar_aging');
            Route::get('top-customers', [SalesAnalyticsController::class,'topCustomers'])->name('top_customers');
            Route::get('payment-allocation', [SalesAnalyticsController::class,'paymentAllocation'])->name('payment_allocation');
            Route::get('credit-notes', [SalesAnalyticsController::class,'creditNotes'])->name('credit_notes');
        });


        Route::prefix('credit-notes')->name('credit-notes.')->group(function (){
            Route::get('/', [SalesCreditNoteController::class,'index'])->name('index');
            
            Route::get('datatable', [SalesCreditNoteController::class,'datatable'])->name('datatable');
        
            Route::get('create', [SalesCreditNoteController::class,'create'])->name('create');
            
            Route::post('/', [SalesCreditNoteController::class,'store'])->name('store');
        
            Route::get('/{creditNote}', [SalesCreditNoteController::class,'show'])->name('show');
            
            Route::get('/{creditNote}/edit', [SalesCreditNoteController::class,'edit'])->name('edit');
            
            Route::put('/{creditNote}', [SalesCreditNoteController::class,'update'])->name('update');
        
            Route::post('/{creditNote}/post', [SalesCreditNoteController::class,'post'])->name('post');
            
            Route::post('/{creditNote}/void', [SalesCreditNoteController::class,'void'])->name('void');
        
            Route::delete('/{creditNote}', [SalesCreditNoteController::class,'destroy'])->name('destroy');
            
            Route::get('invoices/select2', [SalesCreditNoteController::class, 'select2Invoices'])
                ->name('invoices.select2');
        
            Route::get('invoices/{invoice}/lines', [SalesCreditNoteController::class, 'invoiceLines'])
                ->name('invoices.lines');
        });

        Route::prefix('deliveries')->name('deliveries.')->group(function () {
            // AJAX helpers
            Route::get('/select2/orders-confirmed', [SalesDeliveryController::class, 'select2ConfirmedOrders'])->name('select2.orders_confirmed');
            
            Route::get('/order-payload/{order}',   [SalesDeliveryController::class, 'orderPayload'])->name('order.payload');
            
            Route::get('/store-available',         [SalesDeliveryController::class, 'storeAvailable'])->name('store.available');
        
            Route::get('/{delivery}/pdf', [SalesDeliveryController::class, 'printPdf'])->name('pdf')->middleware('can:sales.deliveries.print');
  
            Route::get('/', [SalesDeliveryController::class, 'index'])->name('index');
            
            Route::get('datatable', [SalesDeliveryController::class, 'datatable'])->name('datatable');
    
            Route::get('create', [SalesDeliveryController::class, 'create'])->name('create');
            
            Route::post('/', [SalesDeliveryController::class, 'store'])->name('store');
    
            Route::get('/{delivery}', [SalesDeliveryController::class, 'show'])->name('show');
            Route::get('/{delivery}/edit', [SalesDeliveryController::class, 'edit'])->name('edit');
            Route::put('/{delivery}', [SalesDeliveryController::class, 'update'])->name('update');
    
            Route::post('/{delivery}/post', [SalesDeliveryController::class, 'post'])->name('post');
            Route::post('/{delivery}/cancel', [SalesDeliveryController::class, 'cancel'])->name('cancel');
    
            Route::delete('/{delivery}', [SalesDeliveryController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('data-flush')
        ->name('data-flush.')
        ->group(function () {
    
            Route::get('/', [SalesDataFlushController::class, 'index'])
                ->name('index')
                ->middleware('can:sales.flush.manage');
    
            Route::post('preview', [SalesDataFlushController::class, 'preview'])
                ->name('preview')
                ->middleware('can:sales.flush.manage');
    
            Route::post('run', [SalesDataFlushController::class, 'run'])
                ->name('run')
                ->middleware('can:sales.flush.manage');
        });
    
        // Sales Invoice
        Route::prefix('invoices')->name('invoices.')->group(function () {

            Route::get('/', [SalesInvoiceController::class, 'index'])
                ->name('index');
        
            Route::get('/datatable', [SalesInvoiceController::class, 'datatable'])
                ->name('datatable');
        
            Route::get('/create', [SalesInvoiceController::class, 'create'])
                ->name('create');
                
            Route::get('{invoice}/print', [SalesInvoiceController::class, 'create'])
                ->name('print');
        
            Route::post('/', [SalesInvoiceController::class, 'store'])
                ->name('store');
        
            Route::get('/{invoice}/edit', [SalesInvoiceController::class, 'edit'])
                ->name('edit');
        
            Route::put('/{invoice}', [SalesInvoiceController::class, 'update'])
                ->name('update');
        
            Route::get('/{invoice}', [SalesInvoiceController::class, 'show'])
                ->name('show');
        
            Route::delete('/{invoice}', [SalesInvoiceController::class, 'destroy'])
                ->name('destroy');
        
            Route::post('/{invoice}/post', [SalesInvoiceController::class, 'post'])
                ->name('post');
        
            Route::post('/{invoice}/cancel', [SalesInvoiceController::class, 'cancel'])
                ->name('cancel');
        
            // Select2 + payload
            Route::get('/select2/orders-confirmed', [SalesInvoiceController::class, 'select2ConfirmedOrders'])
                ->name('select2.orders_confirmed');
        
            Route::get('/order-payload/{order}', [SalesInvoiceController::class, 'orderPayload'])
                ->name('order_payload');
        
            // PDF print
            Route::get('/{invoice}/pdf', [SalesInvoiceController::class, 'pdf'])
                ->name('pdf');
        });
        
        // Sales Orders
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [SalesOrderController::class, 'index'])
                ->name('index')->middleware('permission:sales.orders.view');
    
            Route::get('create', [SalesOrderController::class, 'create'])
                ->name('create')->middleware('permission:sales.orders.create');
    
            Route::post('/', [SalesOrderController::class, 'store'])
                ->name('store')->middleware('permission:sales.orders.store');
                
            // Datatable / Select2 / Lines
            Route::get('orders-datatable', [SalesOrderController::class, 'datatable'])
                ->name('datatable')->middleware('permission:sales.orders.datatable');
            Route::get(
                    'orders/select2-by-status',                 [SalesOrderController::class, 'select2ByStatus']
                )->name('select2ByStatus');    
                
            Route::get('select2', [SalesOrderController::class, 'select2'])
                ->name('select2')->middleware('permission:sales.orders.select2');
                
            Route::get('{order}', [SalesOrderController::class, 'show'])
                ->name('show')->middleware('permission:sales.orders.show');
    
            Route::get('{order}/edit', [SalesOrderController::class, 'edit'])
                ->name('edit')->middleware('permission:sales.orders.edit');
    
            Route::put('{order}', [SalesOrderController::class, 'update'])
                ->name('update')->middleware('permission:sales.orders.update');
    
            Route::post('{order}/confirm', [SalesOrderController::class, 'confirm'])
                ->name('confirm')->middleware('permission:sales.orders.confirm');
    
            Route::post('{order}/unconfirm', [SalesOrderController::class, 'unconfirm'])
                ->name('unconfirm')->middleware('permission:sales.orders.confirm');
                
            Route::post('{order}/cancel', [SalesOrderController::class, 'cancel'])
                ->name('cancel')->middleware('permission:sales.orders.cancel');
    
            Route::delete('{order}', [SalesOrderController::class, 'destroy'])
                ->name('destroy')->middleware('permission:sales.orders.delete');
    
    
            // For delivery note auto-load: /admin/sales/orders/{id}/lines
            Route::get('{order}/lines', [SalesOrderController::class, 'lines'])
                ->name('lines')->middleware('permission:sales.orders.lines');
        });

        // Sales Quotes
        Route::prefix('quotes')->name('quotes.')->group(function () {
            Route::get('/', [SalesQuoteController::class, 'index'])
                ->name('index')->middleware('permission:sales.quotes.view');

            Route::get('create', [SalesQuoteController::class, 'create'])
                ->name('create')->middleware('permission:sales.quotes.create');

            Route::post('/', [SalesQuoteController::class, 'store'])
                ->name('store')->middleware('permission:sales.quotes.create');

            Route::get('datatable', [SalesQuoteController::class, 'datatable'])
                ->name('datatable')->middleware('permission:sales.quotes.view');

            Route::get('select2', [SalesQuoteController::class, 'select2'])
                ->name('select2')->middleware('permission:sales.quotes.view');

            Route::get('{quote}', [SalesQuoteController::class, 'show'])
                ->name('show')->middleware('permission:sales.quotes.view');

            Route::get('{quote}/edit', [SalesQuoteController::class, 'edit'])
                ->name('edit')->middleware('permission:sales.quotes.edit');

            Route::put('{quote}', [SalesQuoteController::class, 'update'])
                ->name('update')->middleware('permission:sales.quotes.edit');

            Route::delete('{quote}', [SalesQuoteController::class, 'destroy'])
                ->name('destroy')->middleware('permission:sales.quotes.delete');

            Route::post('{quote}/send', [SalesQuoteController::class, 'send'])
                ->name('send')->middleware('permission:sales.quotes.send');

            Route::post('{quote}/win', [SalesQuoteController::class, 'win'])
                ->name('win')->middleware('permission:sales.quotes.win');

            Route::post('{quote}/reject', [SalesQuoteController::class, 'reject'])
                ->name('reject')->middleware('permission:sales.quotes.reject');

            Route::post('{quote}/expire', [SalesQuoteController::class, 'expire'])
                ->name('expire')->middleware('permission:sales.quotes.expire');

            Route::post('{quote}/review', [SalesQuoteController::class, 'review'])
                ->name('review')->middleware('permission:sales.quotes.review');

            Route::post('{quote}/convert', [SalesQuoteController::class, 'convert'])
                ->name('convert')->middleware('permission:sales.quotes.convert');

            Route::get('{quote}/pdf', [SalesQuoteController::class, 'pdf'])
                ->name('pdf')->middleware('permission:sales.quotes.view');
        });

        Route::prefix('payments')->name('payments.')->group(function () {

            // Payments
            Route::get('/', [SalesPaymentController::class, 'index'])->name('index');
            
            Route::get('datatable', [SalesPaymentController::class, 'datatable'])->name('datatable');
        
            Route::get('create', [SalesPaymentController::class, 'create'])->name('create');
            
            Route::post('/', [SalesPaymentController::class, 'store'])->name('store');
        
            Route::get('/{payment}/edit', [SalesPaymentController::class, 'edit'])->name('edit');
            
            Route::get('/{payment}', [SalesPaymentController::class, 'show'])->name('show');
            
            Route::put('/{payment}', [SalesPaymentController::class, 'update'])->name('update');
        
            Route::post('/{payment}/post', [SalesPaymentController::class, 'post'])->name('post');
            Route::post('/{payment}/void', [SalesPaymentController::class, 'void'])->name('void');
            Route::delete('/{payment}', [SalesPaymentController::class, 'destroy'])->name('destroy');
        
            // Allocation helpers
            Route::get('/{payment}/allocations', [SalesPaymentController::class, 'allocations'])->name('allocations');
            Route::post('/{payment}/allocations', [SalesPaymentController::class, 'saveAllocations'])->name('allocations.save');
        
            Route::get('/{payment}/print', [SalesPaymentController::class, 'print'])->name('print');
            
            Route::get('/{payment}/pdf',   [SalesPaymentController::class, 'pdf'])->name('pdf');
    
            Route::get('/{payment}/receipt/verify', [SalesPaymentController::class, 'verifyReceipt'])
            ->name('receipt.verify');

            // Select2 invoices by customer (unpaid)
            Route::get('/select2/unpaid-invoices', [SalesPaymentController::class, 'select2UnpaidInvoices'])->name('select2.unpaid_invoices');
        });

        Route::prefix('price-lists')->name('price-lists.')->group(function () {
 
            // ── List management ───────────────────────────────────────────────────
            Route::get('/',            [PriceListController::class, 'index'])
                ->name('index');
            Route::get('/datatable',   [PriceListController::class, 'datatable'])
                ->name('datatable');
            Route::get('/select2',     [PriceListController::class, 'select2'])
                ->name('select2');
         
            // AJAX: resolve price for a variant (called from order create form)
            Route::get('/resolve',     [PriceListController::class, 'resolve'])
                ->name('resolve');
         
            Route::post('/',           [PriceListController::class, 'store'])
                ->name('store');
            Route::post('/bulk-delete',[PriceListController::class, 'bulkDelete'])
                ->name('bulk-delete');
         
            // Per-list routes
            Route::get('/{priceList}', [PriceListController::class, 'show'])
                ->name('show');
            Route::put('/{priceList}', [PriceListController::class, 'update'])
                ->name('update');
            Route::delete('/{priceList}', [PriceListController::class, 'destroy'])
                ->name('destroy');
         
            // Items (nested)
            Route::get('/{priceList}/items/datatable', [PriceListController::class, 'itemsDatatable'])
                ->name('items.datatable');
            Route::post('/{priceList}/items',          [PriceListController::class, 'storeItem'])
                ->name('items.store');
            Route::put('/{priceList}/items/{item}',    [PriceListController::class, 'updateItem'])
                ->name('items.update');
            Route::delete('/{priceList}/items/{item}', [PriceListController::class, 'destroyItem'])
                ->name('items.destroy');
        });
         
        Route::prefix('pricing-rules')->name('pricing-rules.')->group(function () {
            Route::get('/',            [PricingRuleController::class, 'index'])
                ->name('index');
            Route::get('/datatable',   [PricingRuleController::class, 'datatable'])
                ->name('datatable');
            Route::post('/',           [PricingRuleController::class, 'store'])
                ->name('store');
            Route::post('/bulk-delete',[PricingRuleController::class, 'bulkDelete'])
                ->name('bulk-delete');
            Route::put('/{pricingRule}',  [PricingRuleController::class, 'update'])
                ->name('update');
            Route::delete('/{pricingRule}', [PricingRuleController::class, 'destroy'])
                ->name('destroy');
            Route::post('/{pricingRule}/toggle', [PricingRuleController::class, 'toggleActive'])
                ->name('toggle');
        });

    });

     /** Procurement */
     Route::prefix('procurement')->name('procurement.')->group(function () {

        Route::prefix('goods-receipts')->name('goods_receipts.')->group(function () {
            Route::get('/', [GoodsReceiptController::class, 'index'])->name('index');
            Route::get('/datatable', [GoodsReceiptController::class, 'datatable'])->name('datatable');
        
            Route::get('/create-from-purchase-order/{purchaseOrder}', [GoodsReceiptController::class, 'createFromPurchaseOrder'])->name('create_from_purchase_order');
        
            Route::get('/{id}', [GoodsReceiptController::class, 'show'])->name('show');
            Route::get('/{id}/details', [GoodsReceiptController::class, 'details'])->name('details');
            Route::get('/{id}/pdf', [GoodsReceiptController::class, 'pdf'])->name('pdf');
        
            Route::post('/', [GoodsReceiptController::class, 'store'])->name('store');
            
            Route::post('{id}/approve', [GoodsReceiptController::class, 'approve'])
                ->name('approve');
        
            Route::post('{id}/post', [GoodsReceiptController::class, 'post'])
                ->name('post');
        
            Route::post('{id}/cancel', [GoodsReceiptController::class, 'cancel'])
                ->name('cancel');
        
            Route::put('/{id}', [GoodsReceiptController::class, 'update'])->name('update');
            Route::delete('/{id}', [GoodsReceiptController::class, 'destroy'])->name('destroy');
        
            Route::post('/{id}/receive', [GoodsReceiptController::class, 'receive'])->name('receive');
            Route::post('/{id}/cancel', [GoodsReceiptController::class, 'cancel'])->name('cancel');
        
            Route::get('/lookups/purchase-orders', [GoodsReceiptController::class, 'select2PurchaseOrders'])->name('lookups.purchase_orders');
            Route::get('/lookups/suppliers', [GoodsReceiptController::class, 'lookupSuppliers'])->name('lookups.suppliers');
            Route::get('/lookups/locations', [GoodsReceiptController::class, 'lookupLocations'])->name('lookups.locations');
            Route::get('/lookups/stores', [GoodsReceiptController::class, 'lookupStores'])->name('lookups.stores');
            Route::get('/lookups/product-variants', [GoodsReceiptController::class, 'lookupProductVariants'])
                ->name('lookups.product_variants');
        });
        
        Route::prefix('guide')->name('guide.')->group(function () {
            Route::get('/', [ProcurementGuideController::class, 'index'])
                ->name('index')->middleware(['can:procurement.guide.view']);
        });

        Route::prefix('purchase-orders')->name('purchase_orders.')->group(function () {
            Route::get('/', [PurchaseOrderController::class, 'index'])->name('index');
            Route::get('/datatable', [PurchaseOrderController::class, 'datatable'])->name('datatable');
        
            Route::get('/create-from-quotation/{quotation}', [PurchaseOrderController::class, 'createFromQuotation'])->name('create_from_quotation');
        
            Route::get('/{id}', [PurchaseOrderController::class, 'show'])->name('show');
            Route::get('/{id}/details', [PurchaseOrderController::class, 'details'])->name('details');
            Route::get('/{id}/pdf', [PurchaseOrderController::class, 'pdf'])->name('pdf');
        
            Route::post('/', [PurchaseOrderController::class, 'store'])->name('store');
            Route::put('/{id}', [PurchaseOrderController::class, 'update'])->name('update');
            Route::delete('/{id}', [PurchaseOrderController::class, 'destroy'])->name('destroy');
        
            Route::post('/{id}/approve', [PurchaseOrderController::class, 'approve'])->name('approve');
            Route::post('/{id}/issue', [PurchaseOrderController::class, 'issue'])->name('issue');
            Route::post('/{id}/close', [PurchaseOrderController::class, 'close'])->name('close');
            Route::post('/{id}/cancel', [PurchaseOrderController::class, 'cancel'])->name('cancel');
        
            Route::get('/lookups/suppliers', [PurchaseOrderController::class, 'select2Suppliers'])->name('lookups.suppliers');
            Route::get('/lookups/supplier-contacts', [PurchaseOrderController::class, 'select2SupplierContacts'])->name('lookups.supplier_contacts');
            Route::get('/lookups/quotations', [PurchaseOrderController::class, 'select2Quotations'])->name('lookups.quotations');
            Route::get('/lookups/locations', [PurchaseOrderController::class, 'select2Locations'])->name('lookups.locations');
            Route::get('/lookups/stores', [PurchaseOrderController::class, 'select2Stores'])->name('lookups.stores');
            Route::get('/lookups/products', [PurchaseOrderController::class, 'select2Products'])->name('lookups.products');
            Route::get('/lookups/units', [PurchaseOrderController::class, 'select2Units'])->name('lookups.units');
            Route::get('/lookups/tax-codes', [PurchaseOrderController::class, 'select2TaxCodes'])->name('lookups.tax_codes');
        });

        Route::prefix('purchase-requisitions')->name('purchase_requisitions.')->group(function () {
            Route::get('/', [PurchaseRequisitionController::class, 'index'])
                ->middleware('permission:procurement.requisitions.view')
                ->name('index');

            Route::get('/datatable', [PurchaseRequisitionController::class, 'datatable'])
                ->middleware('permission:procurement.requisitions.view')
                ->name('datatable');

            Route::get('/{id}', [PurchaseRequisitionController::class, 'show'])
                ->middleware('permission:procurement.requisitions.view')
                ->name('show');

            Route::post('/', [PurchaseRequisitionController::class, 'store'])
                ->middleware('permission:procurement.requisitions.create')
                ->name('store');

            Route::put('/{id}', [PurchaseRequisitionController::class, 'update'])
                ->middleware('permission:procurement.requisitions.edit')
                ->name('update');

            Route::delete('/{id}', [PurchaseRequisitionController::class, 'destroy'])
                ->middleware('permission:procurement.requisitions.delete')
                ->name('destroy');

            Route::post('/{id}/submit', [PurchaseRequisitionController::class, 'submit'])
                ->middleware('permission:procurement.requisitions.submit')
                ->name('submit');

            Route::post('/{id}/approve', [PurchaseRequisitionController::class, 'approve'])
                ->middleware('permission:procurement.requisitions.approve')
                ->name('approve');

            Route::post('/{id}/reject', [PurchaseRequisitionController::class, 'reject'])
                ->middleware('permission:procurement.requisitions.approve')
                ->name('reject');

            Route::get('/lookups/products', [PurchaseRequisitionController::class, 'select2Products'])
                ->middleware('permission:procurement.requisitions.view')
                ->name('lookups.products');

            Route::get('/lookups/units', [PurchaseRequisitionController::class, 'select2Units'])
                ->middleware('permission:procurement.requisitions.view')
                ->name('lookups.units');

            Route::get('/lookups/tax-codes', [PurchaseRequisitionController::class, 'select2TaxCodes'])
                ->middleware('permission:procurement.requisitions.view')
                ->name('lookups.tax_codes');

            Route::get('/lookups/locations', [PurchaseRequisitionController::class, 'select2Locations'])
                ->middleware('permission:procurement.requisitions.view')
                ->name('lookups.locations');

            Route::get('/lookups/stores', [PurchaseRequisitionController::class, 'select2Stores'])
                ->middleware('permission:procurement.requisitions.view')
                ->name('lookups.stores');
                
            Route::get('/{id}/details', [PurchaseRequisitionController::class, 'details'])
                ->middleware('permission:procurement.requisitions.view')
                ->name('details');
            
            Route::get('/{id}/pdf', [PurchaseRequisitionController::class, 'pdf'])
                ->middleware('permission:procurement.requisitions.view')
                ->name('pdf');
        });
        
        Route::prefix('rfqs')->name('rfqs.')->group(function () {
            Route::get('/', [RequestForQuotationController::class, 'index'])
                ->middleware('permission:procurement.rfqs.view')
                ->name('index');
        
            Route::get('/datatable', [RequestForQuotationController::class, 'datatable'])
                ->middleware('permission:procurement.rfqs.view')
                ->name('datatable');
        
            Route::get('/create-from-requisition/{requisitionId}', [RequestForQuotationController::class, 'createFromRequisition'])
                ->middleware('permission:procurement.rfqs.create')
                ->name('create_from_requisition');
        
            Route::get('/{id}', [RequestForQuotationController::class, 'show'])
                ->middleware('permission:procurement.rfqs.view')
                ->name('show');
        
            Route::get('/{id}/details', [RequestForQuotationController::class, 'details'])
                ->middleware('permission:procurement.rfqs.view')
                ->name('details');
        
            Route::get('/{id}/pdf', [RequestForQuotationController::class, 'pdf'])
                ->middleware('permission:procurement.rfqs.view')
                ->name('pdf');
        
            Route::post('/', [RequestForQuotationController::class, 'store'])
                ->middleware('permission:procurement.rfqs.create')
                ->name('store');
        
            Route::put('/{id}', [RequestForQuotationController::class, 'update'])
                ->middleware('permission:procurement.rfqs.edit')
                ->name('update');
        
            Route::delete('/{id}', [RequestForQuotationController::class, 'destroy'])
                ->middleware('permission:procurement.rfqs.delete')
                ->name('destroy');
        
            Route::post('/{id}/send', [RequestForQuotationController::class, 'send'])
                ->middleware('permission:procurement.rfqs.send')
                ->name('send');
        
            Route::post('/{id}/close', [RequestForQuotationController::class, 'close'])
                ->middleware('permission:procurement.rfqs.close')
                ->name('close');
        
            Route::post('/{id}/award', [RequestForQuotationController::class, 'award'])
                ->middleware('permission:procurement.rfqs.award')
                ->name('award');
        
            Route::get('/lookups/requisitions', [RequestForQuotationController::class, 'select2Requisitions'])
                ->middleware('permission:procurement.rfqs.view')
                ->name('lookups.requisitions');
        
            Route::get('/lookups/products', [RequestForQuotationController::class, 'select2Products'])
                ->middleware('permission:procurement.rfqs.view')
                ->name('lookups.products');
        
            Route::get('/lookups/units', [RequestForQuotationController::class, 'select2Units'])
                ->middleware('permission:procurement.rfqs.view')
                ->name('lookups.units');
        
            Route::get('/lookups/tax-codes', [RequestForQuotationController::class, 'select2TaxCodes'])
                ->middleware('permission:procurement.rfqs.view')
                ->name('lookups.tax_codes');
        
            Route::get('/lookups/suppliers', [RequestForQuotationController::class, 'select2Suppliers'])
                ->middleware('permission:procurement.rfqs.view')
                ->name('lookups.suppliers');
            Route::get('/lookups/supplier-contacts', [RequestForQuotationController::class, 'select2SupplierContacts'])
                ->middleware('permission:procurement.rfqs.view')
                ->name('lookups.supplier_contacts');
        });
  
           

        Route::prefix('supplier-quotations')->name('supplier_quotations.')->group(function () {
            Route::get('/', [SupplierQuotationController::class, 'index'])->name('index');
            Route::get('/datatable', [SupplierQuotationController::class, 'datatable'])->name('datatable');
        
            Route::get('/create-from-rfq/{rfq}', [SupplierQuotationController::class, 'createFromRfq'])->name('create_from_rfq');
            Route::get('/{id}', [SupplierQuotationController::class, 'show'])->name('show');
            Route::get('/{id}/details', [SupplierQuotationController::class, 'details'])->name('details');
            Route::get('/{id}/pdf', [SupplierQuotationController::class, 'pdf'])->name('pdf');
            
            Route::post('/', [SupplierQuotationController::class, 'store'])->name('store');
            Route::put('/{id}', [SupplierQuotationController::class, 'update'])->name('update');
            Route::delete('/{id}', [SupplierQuotationController::class, 'destroy'])->name('destroy');
        
            Route::post('/{id}/submit', [SupplierQuotationController::class, 'submit'])->name('submit');
            Route::post('/{id}/review', [SupplierQuotationController::class, 'review'])->name('review');
            Route::post('/{id}/accept', [SupplierQuotationController::class, 'accept'])->name('accept');
            Route::post('/{id}/reject', [SupplierQuotationController::class, 'reject'])->name('reject');
        
            Route::get('/lookups/rfqs', [SupplierQuotationController::class, 'select2Rfqs'])->name('lookups.rfqs');
            Route::get('/lookups/rfq-suppliers', [SupplierQuotationController::class, 'select2RfqSuppliers'])->name('lookups.rfq_suppliers');
            Route::get('/lookups/products', [SupplierQuotationController::class, 'select2Products'])->name('lookups.products');
            Route::get('/lookups/units', [SupplierQuotationController::class, 'select2Units'])->name('lookups.units');
            Route::get('/lookups/tax-codes', [SupplierQuotationController::class, 'select2TaxCodes'])->name('lookups.tax_codes');
        });
        
    });

     /** CRM */
     Route::prefix('crm')->name('crm.')->group(function () {
         
        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::prefix('customer-segmentation')->name('customer_segmentation.')->group(function () {

                Route::get('/', [CustomerSegmentationController::class, 'index'])
                    ->middleware('permission:crm.analytics.customer_segmentation.view')
                    ->name('index');
        
                Route::get('/summary', [CustomerSegmentationController::class, 'summary'])
                    ->middleware('permission:crm.analytics.customer_segmentation.view')
                    ->name('summary');
        
                Route::get('/datatable', [CustomerSegmentationController::class, 'datatable'])
                    ->middleware('permission:crm.analytics.customer_segmentation.view')
                    ->name('datatable');
            
            });
        });
           Route::prefix('activities')->name('activities.')->group(function () {
               
               Route::prefix('analytics')->name('analytics.')->group(function (){

                 Route::get('/', [ActivityAnalyticsController::class, 'index'])
                    ->name('index')
                    ->middleware('permission:crm.activities.analytics.view');
                
                  Route::get('/kpis', [ActivityAnalyticsController::class, 'kpis'])
                    ->name('kpis')
                    ->middleware('permission:crm.activities.analytics.view');
                
                  Route::get('/charts', [ActivityAnalyticsController::class, 'charts'])
                    ->name('charts')
                    ->middleware('permission:crm.activities.analytics.view');
                
                });

                Route::get('/', [ActivityController::class, 'index'])
                ->middleware('permission:crm.activities.view')
                ->name('index');
        
                Route::get('/datatable', [ActivityController::class, 'datatable'])
                ->middleware('permission:crm.activities.view')
                ->name('datatable');
            
                Route::post('/', [ActivityController::class, 'store'])
                    ->middleware('permission:crm.activities.create')
                    ->name('store');
            
                Route::put('/{activity}', [ActivityController::class, 'update'])
                    ->middleware('permission:crm.activities.edit')
                    ->name('update');
            
                Route::delete('/{activity}', [ActivityController::class, 'destroy'])
                    ->middleware('permission:crm.activities.delete')
                    ->name('destroy');
            
                Route::post('/bulk-delete', [ActivityController::class, 'bulkDelete'])
                    ->middleware('permission:crm.activities.bulk_delete')
                    ->name('bulk_delete');     
            });

            Route::prefix('customers')->name('customers.')->group(function () {
                Route::prefix('analytics')->name('analytics.')->group(function () {
                  Route::get('/', [CustomersAnalyticsController::class, 'index'])
                    ->name('index')
                    ->middleware('permission:crm.customers.analytics.view');
                
                  Route::get('/kpis', [CustomersAnalyticsController::class, 'kpis'])
                    ->name('kpis')
                    ->middleware('permission:crm.customers.analytics.view');
                
                  Route::get('/charts', [CustomersAnalyticsController::class, 'charts'])
                    ->name('charts')
                    ->middleware('permission:crm.customers.analytics.view');
                });
                
                // ── Contacts ──────────────────────────────────────────────────────────────
                Route::get('/{customer}/contacts/datatable',
                    [CustomerController::class, 'contactsDatatable'])
                    ->name('contacts.datatable');
             
                Route::post('/{customer}/contacts',
                    [CustomerController::class, 'storeContact'])
                    ->name('contacts.store');
             
                Route::put('/{customer}/contacts/{contact}',
                    [CustomerController::class, 'updateContact'])
                    ->name('contacts.update');
             
                Route::delete('/{customer}/contacts/{contact}',
                    [CustomerController::class, 'destroyContact'])
                    ->name('contacts.destroy');
             
                // ── Addresses ─────────────────────────────────────────────────────────────
                Route::get('/{customer}/addresses/datatable',
                    [CustomerController::class, 'addressesDatatable'])
                    ->name('addresses.datatable');
             
                Route::post('/{customer}/addresses',
                    [CustomerController::class, 'storeAddress'])
                    ->name('addresses.store');
             
                Route::put('/{customer}/addresses/{address}',
                    [CustomerController::class, 'updateAddress'])
                    ->name('addresses.update');
             
                Route::delete('/{customer}/addresses/{address}',
                    [CustomerController::class, 'destroyAddress'])
                    ->name('addresses.destroy');
             
                // ── Edit page (was referenced in show.blade but was missing) ──────────────
                Route::get('/{id}/edit',
                    [CustomerController::class, 'edit'])
                    ->name('edit');
        
                Route::prefix('segmentation-presets')->name('segmentation_presets.')->middleware(['auth'])->group(function () {
                    // Presets
                    Route::get('/', [CustomerSegmentationPresetsController::class,'index'])
                        ->middleware('permission:crm.customers.segmentation_presets.view')
                        ->name('index');
                
                    Route::get('/datatable', [CustomerSegmentationPresetsController::class,'datatable'])
                        ->middleware('permission:crm.customers.segmentation_presets.view')
                        ->name('datatable');
                
                    Route::get('/select2', [CustomerSegmentationPresetsController::class,'select2'])
                        ->middleware('permission:crm.customers.segmentation_presets.view')
                        ->name('select2');
                
                    Route::get('/{preset}', [CustomerSegmentationPresetsController::class,'show'])
                        ->middleware('permission:crm.customers.segmentation_presets.view')
                        ->name('show');
                
                    Route::post('/', [CustomerSegmentationPresetsController::class,'store'])
                        ->middleware('permission:crm.customers.segmentation_presets.create')
                        ->name('store');
                
                    Route::put('/{preset}', [CustomerSegmentationPresetsController::class,'update'])
                        ->middleware('permission:crm.customers.segmentation_presets.update')
                        ->name('update');
                
                    Route::delete('/{preset}', [CustomerSegmentationPresetsController::class,'destroy'])
                        ->middleware('permission:crm.customers.segmentation_presets.delete')
                        ->name('destroy');
                });
                
                Route::get('/{customer}/price-lists', function (\Modules\CRM\Models\Customer $customer) {
                    return \Modules\Sales\Models\PriceList::active()
                        ->forSale()
                        ->whereHas('customers', fn($q) => $q->where('customer_id', $customer->id))
                        ->get(['id', 'name', 'currency_code', 'is_default']);
                })->name('price-lists');

            });

            
            Route::prefix('dashboard')->name('dashboard.')->group(function () {

              Route::get('/', [CrmDashboardController::class, 'index'])
                ->name('index')
                ->middleware('permission:crm.dashboard.view');
            
              Route::get('/summary', [CrmDashboardController::class, 'summary'])
                ->name('summary')
                ->middleware('permission:crm.dashboard.view');
            
              Route::get('/charts', [CrmDashboardController::class, 'charts'])
                ->name('charts')
                ->middleware('permission:crm.dashboard.view');
            
            });
            
            Route::prefix('docs')->name('docs.')->group(function () {
                Route::prefix('workflow-privileges')->name('workflow_privileges.')->group(function () {
                    Route::get('/', [CrmDocsController::class, 'workflowPrivileges'])
                        ->name('index')
                        ->middleware('permission:crm.docs.view');
                
                    // Optional downloads (only if you want to serve files from /public/docs/)
                    Route::get('/download/pdf', [CrmDocsController::class, 'downloadPdf'])
                        ->name('download.pdf')
                        ->middleware('permission:crm.docs.view');
                
                    Route::get('/download/html', [CrmDocsController::class, 'downloadHtml'])
                        ->name('download.html')
                        ->middleware('permission:crm.docs.view');
                });
            });
            
            Route::prefix('interactions')->name('interactions.')->group(function () {
              
                Route::prefix('analytics')->name('analytics.')->group(function () {

                  Route::get('/', [InteractionAnalyticsController::class, 'index'])
                    ->name('index')
                    ->middleware('permission:crm.interactions.analytics.view');
                
                  Route::get('/kpis', [InteractionAnalyticsController::class, 'kpis'])
                    ->name('kpis')
                    ->middleware('permission:crm.interactions.analytics.view');
                
                  Route::get('/charts', [InteractionAnalyticsController::class, 'charts'])
                    ->name('charts')
                    ->middleware('permission:crm.interactions.analytics.view');
                
                });

               // Interactions
                Route::get('/', [InteractionsController::class, 'index'])
                    ->name('index')
                    ->middleware('permission:crm.interactions.view');
            
                Route::get('/datatable', [InteractionsController::class, 'datatable'])
                    ->name('datatable')
                    ->middleware('permission:crm.interactions.view');
            
                Route::post('/', [InteractionsController::class, 'store'])
                    ->name('store')
                    ->middleware('permission:crm.interactions.create');
            
                Route::put('/{interaction}', [InteractionsController::class, 'update'])
                    ->name('update')
                    ->middleware('permission:crm.interactions.update');
            
                Route::delete('/{interaction}', [InteractionsController::class, 'destroy'])
                    ->name('destroy')
                    ->middleware('permission:crm.interactions.delete');
            
                Route::post('/bulk-delete', [InteractionsController::class, 'bulkDelete'])
                    ->name('bulk_delete')
                    ->middleware('permission:crm.interactions.delete');
            
                // Select2 for Lead/Opportunity interactables (Customer uses CustomerController@select2)
                Route::get('/fetch-interactables', [InteractionsController::class, 'fetchInteractables'])
                    ->name('fetch_interactables')
                    ->middleware('permission:crm.interactions.view');     
          });

          Route::prefix('leads')->name('leads.')->group(function () {
                Route::prefix('analytics')->name('analytics.')->group(function () {

                  Route::get('/', [LeadAnalyticsController::class, 'index'])
                    ->name('index')
                    ->middleware('permission:crm.leads.analytics.view');
                
                  Route::get('/kpis', [LeadAnalyticsController::class, 'kpis'])
                    ->name('kpis')
                    ->middleware('permission:crm.leads.analytics.view');
                
                  Route::get('/charts', [LeadAnalyticsController::class, 'charts'])
                    ->name('charts')
                    ->middleware('permission:crm.leads.analytics.view');
                
                });

              // Companies select2
                Route::get('/', [LeadController::class, 'index'])
                    ->middleware('permission:crm.leads.view')
                    ->name('index');
            
                Route::get('/datatable', [LeadController::class, 'datatable'])
                    ->middleware('permission:crm.leads.view')
                    ->name('datatable');
            
                Route::post('/', [LeadController::class, 'store'])
                    ->middleware('permission:crm.leads.create')
                    ->name('store');
            
                Route::put('/{lead}', [LeadController::class, 'update'])
                    ->middleware('permission:crm.leads.edit')
                    ->name('update');
            
                Route::delete('/{lead}', [LeadController::class, 'destroy'])
                    ->middleware('permission:crm.leads.delete')
                    ->name('destroy');
            
                Route::post('/bulk-delete', [LeadController::class, 'bulkDelete'])
                    ->middleware('permission:crm.leads.bulk_delete')
                    ->name('bulk_delete');     
          });

          Route::prefix('notes')->name('notes.')->group(function () {
              
                Route::prefix('analytics')->name('analytics.')->group(function () {

                  Route::get('/', [NotesAnalyticsController::class, 'index'])
                    ->name('index')
                    ->middleware('permission:crm.notes.analytics.view');
                
                  Route::get('/kpis', [NotesAnalyticsController::class, 'kpis'])
                    ->name('kpis')
                    ->middleware('permission:crm.notes.analytics.view');
                
                  Route::get('/charts', [NotesAnalyticsController::class, 'charts'])
                    ->name('charts')
                    ->middleware('permission:crm.notes.analytics.view');
                
                });

                Route::get('/', [NotesController::class, 'index'])
                    ->name('index')
                    ->middleware('permission:crm.notes.view');
            
                Route::get('/datatable', [NotesController::class, 'datatable'])
                    ->name('datatable')
                    ->middleware('permission:crm.notes.view');
            
                Route::post('/', [NotesController::class, 'store'])
                    ->name('store')
                    ->middleware('permission:crm.notes.create');
            
                Route::put('/{note}', [NotesController::class, 'update'])
                    ->name('update')
                    ->middleware('permission:crm.notes.update');
            
                Route::delete('/{note}', [NotesController::class, 'destroy'])
                    ->name('destroy')
                    ->middleware('permission:crm.notes.delete');
            
                Route::post('/bulk-delete', [NotesController::class, 'bulkDelete'])
                    ->name('bulk_delete')
                    ->middleware('permission:crm.notes.bulk_delete');
            
                Route::get('/fetch-notables', [NotesController::class, 'fetchNotables'])
                    ->name('fetch_notables')
                    ->middleware('permission:crm.notes.view');
     
          });

          Route::prefix('opportunities')->name('opportunities.')->group(function () {
                // Opportunities
                Route::prefix('analytics')->name('analytics.')->group(function () {
                  Route::get('/', [OpportunityAnalyticsController::class, 'index'])
                    ->name('index')
                    ->middleware('permission:crm.opportunities.analytics.view');
                
                  Route::get('/kpis', [OpportunityAnalyticsController::class, 'kpis'])
                    ->name('kpis')
                    ->middleware('permission:crm.opportunities.analytics.view');
                
                  Route::get('/charts', [OpportunityAnalyticsController::class, 'charts'])
                    ->name('charts')
                    ->middleware('permission:crm.opportunities.analytics.view');
                
                });
                
                Route::get('/',            [OpportunityController::class, 'index'])->middleware('permission:crm.opportunities.view')->name('index');
                Route::get('/datatable',  [OpportunityController::class, 'datatable'])->middleware('permission:crm.opportunities.view')->name('datatable');
            
                Route::post('opportunities',           [OpportunityController::class, 'store'])->middleware('permission:crm.opportunities.create')->name('store');
                Route::put('/{opportunity}',[OpportunityController::class, 'update'])->middleware('permission:crm.opportunities.update')->name('update');
            
                Route::delete('/{opportunity}',[OpportunityController::class, 'destroy'])->middleware('permission:crm.opportunities.delete')->name('destroy');
                Route::post('/bulk-delete',[OpportunityController::class, 'bulkDelete'])->middleware('permission:crm.opportunities.delete')->name('bulk_delete');     
          });

          Route::prefix('support-tickets')->name('support_tickets.')->group(function () {
              
                Route::prefix('analytics')->name('analytics.')->group(function () {

                    Route::get('/', [SupportTicketsController::class, 'analytics'])
                        ->name('index')
                        ->middleware('permission:crm.support_tickets.analytics.view');
                
                    Route::get('/kpis', [SupportTicketsController::class, 'analyticsKpis'])
                        ->name('kpis')
                        ->middleware('permission:crm.support_tickets.analytics.view');
                
                    Route::get('/trends', [SupportTicketsController::class, 'analyticsTrends'])
                        ->name('trends')
                        ->middleware('permission:crm.support_tickets.analytics.view');
                
                    Route::get('/aging', [SupportTicketsController::class, 'analyticsAging'])
                        ->name('aging')
                        ->middleware('permission:crm.support_tickets.analytics.view');
                
                    Route::get('/agents', [SupportTicketsController::class, 'analyticsAgents'])
                        ->name('agents')
                        ->middleware('permission:crm.support_tickets.analytics.view');
                    
                    Route::get('/export/csv', [SupportTicketsController::class, 'exportAnalyticsCsv'])
                        ->name('export.csv')
                        ->middleware('permission:crm.support_tickets.analytics.export');
                
                    Route::get('/export/pdf', [SupportTicketsController::class, 'exportAnalyticsPdf'])
                        ->name('export.pdf')
                        ->middleware('permission:crm.support_tickets.analytics.export');
                                
                });

                Route::get('/', [SupportTicketsController::class, 'index'])
                    ->name('index')
                    ->middleware('permission:crm.support_tickets.view');
            
                Route::get('/datatable', [SupportTicketsController::class, 'datatable'])
                    ->name('datatable')
                    ->middleware('permission:crm.support_tickets.view');
            
                Route::post('/', [SupportTicketsController::class, 'store'])
                    ->name('store')
                    ->middleware('permission:crm.support_tickets.create');
            
                Route::get('/{ticket}', [SupportTicketsController::class, 'show'])
                    ->name('show')
                    ->middleware('permission:crm.support_tickets.view');
            
                Route::put('/{ticket}', [SupportTicketsController::class, 'update'])
                    ->name('update')
                    ->middleware('permission:crm.support_tickets.update');
            
                Route::delete('/{ticket}', [SupportTicketsController::class, 'destroy'])
                    ->name('destroy')
                    ->middleware('permission:crm.support_tickets.delete');
            
                Route::post('/bulk-delete', [SupportTicketsController::class, 'bulkDelete'])
                    ->name('bulk_delete')
                    ->middleware('permission:crm.support_tickets.delete');
            
                // Thread (comments/updates)
                Route::post('/{ticket}/comments', [SupportTicketsController::class, 'addComment'])
                    ->name('comments')
                    ->middleware('permission:crm.support_tickets.update');
            
                // Attachments
                Route::post('/{ticket}/attachments', [SupportTicketsController::class, 'addAttachment'])
                    ->name('attachment')
                    ->middleware('permission:crm.support_tickets.update');
            
                Route::delete('/{ticket}/attachments/{attachment}', [SupportTicketsController::class, 'deleteAttachment'])
                    ->name('attachment_delete')
                    ->middleware('permission:crm.support_tickets.update');     
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
                    Route::delete('/bulk-delete', [EmployeeController::class, 'bulkDeleteLeave'])->name('bulk-delete');
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
                    Route::delete('/bulk-delete', [TrainingController::class, 'bulkDelete'])->name('bulk-delete');
                    Route::delete('/{training}', [TrainingController::class, 'destroy'])->name('destroy');      
               }); 

                Route::get('/select2', [EmployeeController::class, 'select2'])->name('select2');
                Route::get('/', [EmployeeController::class, 'index'])->name('index');
                Route::post('/', [EmployeeController::class, 'store'])->name('store');
                Route::get('/datatable', [EmployeeController::class, 'datatable'])->name('datatable');
                
                Route::post('/attendance', [EmployeeController::class, 'storeAttendance'])->name('attendances.store');
                Route::post('/leaves', [EmployeeController::class, 'storeLeave'])->name('leaves.store');
                Route::post('/payrolls', [PayrollController::class, 'store'])->name('payrolls.store');
                Route::post('/performances', [PerformanceController::class, 'store'])->name('performances.store');
                
            
                Route::get('/{employee}', [EmployeeController::class, 'show'])->name('show');
                Route::get('/{employee}/attendance/datatable', [EmployeeController::class, 'employeeAttendanceDatatable'])->name('show.attendance.datatable');
                Route::get('/{employee}/leave/datatable', [EmployeeController::class, 'employeeLeaveDatatable'])->name('show.leave.datatable');
                Route::get('/{employee}/payroll/datatable', [EmployeeController::class, 'employeePayrollDatatable'])->name('show.payroll.datatable');
                Route::get('/{employee}/performance/datatable', [EmployeeController::class, 'employeePerformanceDatatable'])->name('show.performance.datatable');
                
                Route::get('/{employee}/edit', [EmployeeController::class, 'edit'])->name('edit');
                Route::put('/{employee}', [EmployeeController::class, 'update'])->name('update');
                Route::delete('/bulk-delete', [EmployeeController::class, 'bulkDelete'])->name('bulk-delete');
                Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->name('destroy');
                
          }); 
          
          // ── Leave Types ───────────────────────────────────────────────────────────────
            Route::prefix('leave-types')->name('leave-types.')->group(function () {
                Route::get('/',          [HrLeaveTypeController::class, 'index'])    ->name('index');
                Route::get('/datatable', [HrLeaveTypeController::class, 'datatable'])->name('datatable');
                Route::get('/select2',   [HrLeaveTypeController::class, 'select2'])  ->name('select2');
                Route::post('/',         [HrLeaveTypeController::class, 'store'])    ->name('store');
                Route::put('/{hrLeaveType}',   [HrLeaveTypeController::class, 'update']) ->name('update');
                Route::delete('/{hrLeaveType}',[HrLeaveTypeController::class, 'destroy'])->name('destroy');
            });
             
            // ── Shifts + Rosters ──────────────────────────────────────────────────────────
            Route::prefix('shifts')->name('shifts.')->group(function () {
                Route::get('/',          [HrShiftController::class, 'index'])    ->name('index');
                Route::get('/datatable', [HrShiftController::class, 'datatable'])->name('datatable');
                Route::get('/select2',   [HrShiftController::class, 'select2'])  ->name('select2');
                Route::post('/',         [HrShiftController::class, 'store'])    ->name('store');
                Route::put('/{hrShift}',   [HrShiftController::class, 'update']) ->name('update');
                Route::delete('/{hrShift}',[HrShiftController::class, 'destroy'])->name('destroy');
            });
             
            Route::prefix('rosters')->name('rosters.')->group(function () {
                Route::get('/',          [HrShiftController::class, 'rosterIndex'])    ->name('index');
                Route::get('/datatable', [HrShiftController::class, 'rosterDatatable'])->name('datatable');
                Route::post('/',         [HrShiftController::class, 'storeRoster'])    ->name('store');
                Route::delete('/{hrRoster}',[HrShiftController::class, 'destroyRoster'])->name('destroy');
            });
             
            // ── Job Grades ────────────────────────────────────────────────────────────────
            Route::prefix('job-grades')->name('job-grades.')->group(function () {
                Route::get('/',          [HrContractController::class, 'gradeIndex'])    ->name('index');
                Route::get('/datatable', [HrContractController::class, 'gradeDatatable'])->name('datatable');
                Route::get('/select2',   [HrContractController::class, 'gradeSelect2'])  ->name('select2');
                Route::post('/',         [HrContractController::class, 'gradeStore'])    ->name('store');
                Route::put('/{hrJobGrade}',   [HrContractController::class, 'gradeUpdate']) ->name('update');
                Route::delete('/{hrJobGrade}',[HrContractController::class, 'gradeDestroy'])->name('destroy');
            });
             
            // ── Job Positions ─────────────────────────────────────────────────────────────
            Route::prefix('job-positions')->name('job-positions.')->group(function () {
                Route::get('/',          [HrContractController::class, 'positionIndex'])    ->name('index');
                Route::get('/datatable', [HrContractController::class, 'positionDatatable'])->name('datatable');
                Route::get('/select2',   [HrContractController::class, 'positionSelect2'])  ->name('select2');
                Route::post('/',         [HrContractController::class, 'positionStore'])    ->name('store');
                Route::put('/{hrJobPosition}',   [HrContractController::class, 'positionUpdate']) ->name('update');
                Route::delete('/{hrJobPosition}',[HrContractController::class, 'positionDestroy'])->name('destroy');
            });
             
            // ── Contracts ─────────────────────────────────────────────────────────────────
            Route::prefix('contracts')->name('contracts.')->group(function () {
                Route::get('/',          [HrContractController::class, 'index'])    ->name('index');
                Route::get('/datatable', [HrContractController::class, 'datatable'])->name('datatable');
                Route::post('/',         [HrContractController::class, 'store'])    ->name('store');
                Route::put('/{hrContract}',          [HrContractController::class, 'update'])    ->name('update');
                Route::delete('/{hrContract}',       [HrContractController::class, 'destroy'])   ->name('destroy');
                Route::post('/{hrContract}/terminate',[HrContractController::class, 'terminate'])->name('terminate');
                // Employee-specific contracts datatable (used in employee show page)
                Route::get('/employee/{employee}/datatable',[HrContractController::class,'employeeContracts'])->name('employee.datatable');
            });
             
            // ── Recruitment ───────────────────────────────────────────────────────────────
            Route::prefix('recruitment')->name('recruitment.')->group(function () {
             
                Route::prefix('openings')->name('openings.')->group(function () {
                    Route::get('/',          [HrRecruitmentController::class, 'openingIndex'])    ->name('index');
                    Route::get('/datatable', [HrRecruitmentController::class, 'openingDatatable'])->name('datatable');
                    Route::post('/',         [HrRecruitmentController::class, 'openingStore'])    ->name('store');
                    Route::get('/{hrJobOpening}',    [HrRecruitmentController::class, 'openingShow'])   ->name('show');
                    Route::put('/{hrJobOpening}',    [HrRecruitmentController::class, 'openingUpdate']) ->name('update');
                    Route::delete('/{hrJobOpening}', [HrRecruitmentController::class, 'openingDestroy'])->name('destroy');
             
                    // Nested applicants
                    Route::get('/{hrJobOpening}/applicants/datatable',
                        [HrRecruitmentController::class, 'applicantDatatable'])->name('applicants.datatable');
                    Route::post('/{hrJobOpening}/applicants',
                        [HrRecruitmentController::class, 'applicantStore'])->name('applicants.store');
                });
             
                // Applicant CRUD (top-level for edit/delete without opening context)
                Route::put('/applicants/{hrApplicant}',    [HrRecruitmentController::class, 'applicantUpdate']) ->name('applicants.update');
                Route::delete('/applicants/{hrApplicant}', [HrRecruitmentController::class, 'applicantDestroy'])->name('applicants.destroy');
             
                // Interviews (nested under applicant)
                Route::post('/applicants/{hrApplicant}/interviews',
                    [HrRecruitmentController::class, 'interviewStore'])->name('interviews.store');
                Route::put('/interviews/{hrInterview}',
                    [HrRecruitmentController::class, 'interviewUpdate'])->name('interviews.update');
                Route::delete('/interviews/{hrInterview}',
                    [HrRecruitmentController::class, 'interviewDestroy'])->name('interviews.destroy');
            });
             
            // ── Payroll Runs ──────────────────────────────────────────────────────────────
            Route::prefix('payroll-runs')->name('payroll-runs.')->group(function () {
                Route::get('/',          [HrPayrollRunController::class, 'index'])    ->name('index');
                Route::get('/datatable', [HrPayrollRunController::class, 'datatable'])->name('datatable');
                Route::post('/',         [HrPayrollRunController::class, 'store'])    ->name('store');
                Route::get('/{hrPayrollRun}',         [HrPayrollRunController::class, 'show'])   ->name('show');
                Route::delete('/{hrPayrollRun}',      [HrPayrollRunController::class, 'destroy'])->name('destroy');
                Route::post('/{hrPayrollRun}/approve',[HrPayrollRunController::class, 'approve'])->name('approve');
                Route::post('/{hrPayrollRun}/post',   [HrPayrollRunController::class, 'post'])   ->name('post');
             
                // Payslips (within run)
                Route::get('/{hrPayrollRun}/payslips/datatable',
                    [HrPayrollRunController::class, 'payslipDatatable'])->name('payslips.datatable');
                Route::put('/{hrPayrollRun}/payslips/{hrPayslip}',
                    [HrPayrollRunController::class, 'payslipUpdate'])->name('payslips.update');
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
               Route::post('/{payroll}/paid', [PayrollController::class, 'togglePaidStatus'])->name('mark-paid');
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

          Route::prefix('boms')->name('boms.')->middleware('permission:production.boms.manage')->group(function () {

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
                    Route::get('/', [BOMItemController::class, 'index'])->name('index');
                    Route::post('/', [BOMItemController::class, 'store'])->name('store');
                    Route::get('/datatable', [BOMItemController::class, 'datatable'])->name('datatable');
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

          Route::prefix('raw-materials')->name('raw-materials.')->middleware('permission:production.raw_materials.manage')->group(function () {

               Route::get('/', [RawMaterialController::class, 'index'])->name('index');
               Route::post('/', [RawMaterialController::class, 'store'])->name('store');
               Route::get('/datatable', [RawMaterialController::class, 'datatable'])->name('datatable');
               Route::get('/{raw_material}/edit', [RawMaterialController::class, 'edit'])->name('edit');
               Route::put('/{raw_material}', [RawMaterialController::class, 'update'])->name('update');
               Route::delete('/bulk-delete', [RawMaterialController::class, 'bulkDelete'])->name('bulk-delete');
               Route::delete('/{raw_material}', [RawMaterialController::class, 'destroy'])->name('destroy');                  
               
          });

          Route::prefix('routings')->name('routings.')->middleware('permission:production.routings.manage')->group(function () {

               Route::get('select2', [RoutingController::class, 'select2'])
               ->name('select2');

               Route::prefix('steps')->name('steps.')->group(function () {
                    Route::get('/', [RoutingStepController::class, 'index'])
                         ->name('index');

                    Route::get('datatable', [RoutingStepController::class, 'datatable'])
                         ->name('datatable_all');

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

          Route::prefix('work-orders')->name('work-orders.')->middleware('permission:production.work_orders.manage')->group(function () {

               Route::get('tasks/{work_order}/select2',  [WorkOrderTaskController::class, 'workOrderTasksSelect2'])
               ->name('tasks.select2');
               
               
               Route::get('{work_order}/boms/items', [WorkOrderMaterialController::class, 'bomItemsDatatable'])->name('boms.items.datatable');

               Route::get(
                    '{work_order}/materials/variants/available',
                    [WorkOrderMaterialsController::class, 'variantsAvailableSelect2']
                  )->name('materials.variants.available');

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
               Route::put('/{wo}',          [\Modules\Production\Http\Controllers\WorkOrderController::class, 'update'])->name('update');
               Route::delete('/{wo}',       [\Modules\Production\Http\Controllers\WorkOrderController::class, 'destroy'])->name('destroy');

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

    Route::prefix('projects')->name('projects.')->group(function () {
        
        Route::prefix('docs')->name('docs.')->group(function () {
            Route::get('/', [ProjectDocsController::class, 'index'])->name('index');
            Route::get('pdf', [ProjectDocsController::class, 'pdf'])->name('pdf');
        });
        Route::get('/', [ProjectController::class, 'index'])->name('index');
        Route::get('/datatable', [ProjectController::class, 'datatable'])->name('datatable');
    
        Route::post('/', [ProjectController::class, 'store'])->name('store');
        Route::put('/{id}', [ProjectController::class, 'update'])->name('update');
        Route::delete('/{id}', [ProjectController::class, 'destroy'])->name('destroy');
    
        Route::get('/lookups/clients', [ProjectController::class, 'lookupClients'])->name('lookups.clients');
        Route::get('/lookups/managers', [ProjectController::class, 'lookupManagers'])->name('lookups.managers');
    });

    Route::prefix('project-budgets')->middleware(['auth'])->group(function () {
        Route::get('/', [ProjectBudgetController::class, 'index'])->name('project_budgets.index');
        Route::get('/datatable', [ProjectBudgetController::class, 'datatable'])->name('project_budgets.datatable');
        Route::get('/{id}/lines', [ProjectBudgetController::class, 'lines'])->name('project_budgets.lines');
    
        Route::post('/', [ProjectBudgetController::class, 'store'])->name('project_budgets.store');
        Route::put('/{id}', [ProjectBudgetController::class, 'update'])->name('project_budgets.update');
        Route::delete('/{id}', [ProjectBudgetController::class, 'destroy'])->name('project_budgets.destroy');
    
        Route::get('/lookups/projects', [ProjectBudgetController::class, 'lookupProjects'])->name('project_budgets.lookups.projects');
        Route::get('/lookups/tasks', [ProjectBudgetController::class, 'lookupTasks'])->name('project_budgets.lookups.tasks');
        Route::get('/lookups/milestones', [ProjectBudgetController::class, 'lookupMilestones'])->name('project_budgets.lookups.milestones');
    });

    Route::prefix('/project-costs')->group(function () {
        Route::get('/', [ProjectCostController::class, 'index'])->name('project_costs.index');
        Route::get('/datatable', [ProjectCostController::class, 'datatable'])->name('project_costs.datatable');
    
        Route::put('/{id}', [ProjectCostController::class, 'update'])->name('project_costs.update');
        Route::delete('/{id}', [ProjectCostController::class, 'destroy'])->name('project_costs.destroy');
    
        Route::get('/lookups/projects', [ProjectCostController::class, 'lookupProjects'])->name('project_costs.lookups.projects');
        Route::get('/lookups/tasks', [ProjectCostController::class, 'lookupTasks'])->name('project_costs.lookups.tasks');
        Route::get('/lookups/milestones', [ProjectCostController::class, 'lookupMilestones'])->name('project_costs.lookups.milestones');
    });

    Route::prefix('project-invoices')->middleware(['auth'])->group(function () {
        Route::get('/', [ProjectInvoiceController::class, 'index'])->name('project_invoices.index');
        Route::get('/datatable', [ProjectInvoiceController::class, 'datatable'])->name('project_invoices.datatable');
        Route::get('/{id}/lines', [ProjectInvoiceController::class, 'lines'])->name('project_invoices.lines');
    
        Route::post('/', [ProjectInvoiceController::class, 'store'])->name('project_invoices.store');
        Route::put('/{id}', [ProjectInvoiceController::class, 'update'])->name('project_invoices.update');
        Route::delete('/{id}', [ProjectInvoiceController::class, 'destroy'])->name('project_invoices.destroy');
    
        Route::post('/{id}/post', [ProjectInvoiceController::class, 'post'])->name('project_invoices.post');
        Route::post('/{id}/void', [ProjectInvoiceController::class, 'void'])->name('project_invoices.void');
    
        Route::get('/lookups/projects', [ProjectInvoiceController::class, 'lookupProjects'])->name('project_invoices.lookups.projects');
        Route::get('/lookups/tasks', [ProjectInvoiceController::class, 'lookupTasks'])->name('project_invoices.lookups.tasks');
        Route::get('/lookups/milestones', [ProjectInvoiceController::class, 'lookupMilestones'])->name('project_invoices.lookups.milestones');
        Route::get('/lookups/timesheets', [ProjectInvoiceController::class, 'lookupBillableTimesheets'])->name('project_invoices.lookups.timesheets');
    });
    
    Route::prefix('project-milestones')->middleware(['auth'])->group(function () {
        Route::get('/', [ProjectMilestoneController::class, 'index'])->name('project_milestones.index');
        Route::get('/datatable', [ProjectMilestoneController::class, 'datatable'])->name('project_milestones.datatable');
    
        Route::post('/', [ProjectMilestoneController::class, 'store'])->name('project_milestones.store');
        Route::put('/{id}', [ProjectMilestoneController::class, 'update'])->name('project_milestones.update');
        Route::delete('/{id}', [ProjectMilestoneController::class, 'destroy'])->name('project_milestones.destroy');
    
        Route::get('/lookups/projects', [ProjectMilestoneController::class, 'lookupProjects'])->name('project_milestones.lookups.projects');
        Route::get('/lookups/owners', [ProjectMilestoneController::class, 'lookupOwners'])->name('project_milestones.lookups.owners');
    });
    

    Route::prefix('project-profitability')->middleware(['auth'])->group(function () {
        Route::get('/', [ProjectProfitabilityDashboardController::class, 'index'])->name('project_profitability.index');
        Route::get('/data', [ProjectProfitabilityDashboardController::class, 'data'])->name('project_profitability.data');
        Route::get('/lookups/projects', [ProjectProfitabilityDashboardController::class, 'lookupProjects'])->name('project_profitability.lookups.projects');
    });
    
    Route::prefix('/project-tasks')->group(function () {
        Route::get('/', [ProjectTaskController::class, 'index'])->name('project_tasks.index');
        Route::get('/datatable', [ProjectTaskController::class, 'datatable'])->name('project_tasks.datatable');
    
        Route::post('/', [ProjectTaskController::class, 'store'])->name('project_tasks.store');
        Route::put('/{id}', [ProjectTaskController::class, 'update'])->name('project_tasks.update');
        Route::delete('/{id}', [ProjectTaskController::class, 'destroy'])->name('project_tasks.destroy');
    
        Route::get('/lookups/projects', [ProjectTaskController::class, 'lookupProjects'])->name('project_tasks.lookups.projects');
        Route::get('/lookups/parent-tasks', [ProjectTaskController::class, 'lookupParentTasks'])->name('project_tasks.lookups.parent_tasks');
        Route::get('/lookups/employees', [ProjectTaskController::class, 'lookupEmployees'])->name('project_tasks.lookups.employees');
    });

    Route::prefix('project-timesheets')->middleware(['auth'])->group(function () {
        Route::get('/', [ProjectTimesheetController::class, 'index'])->name('project_timesheets.index');
        Route::get('/datatable', [ProjectTimesheetController::class, 'datatable'])->name('project_timesheets.datatable');
    
        Route::post('/', [ProjectTimesheetController::class, 'store'])->name('project_timesheets.store');
        Route::put('/{id}', [ProjectTimesheetController::class, 'update'])->name('project_timesheets.update');
        Route::delete('/{id}', [ProjectTimesheetController::class, 'destroy'])->name('project_timesheets.destroy');
    
        Route::post('/{id}/submit', [ProjectTimesheetController::class, 'submit'])->name('project_timesheets.submit');
        Route::post('/{id}/approve', [ProjectTimesheetController::class, 'approve'])->name('project_timesheets.approve');
        Route::post('/{id}/reject', [ProjectTimesheetController::class, 'reject'])->name('project_timesheets.reject');
    
        Route::get('/lookups/projects', [ProjectTimesheetController::class, 'lookupProjects'])->name('project_timesheets.lookups.projects');
        Route::get('/lookups/tasks', [ProjectTimesheetController::class, 'lookupTasks'])->name('project_timesheets.lookups.tasks');
        Route::get('/lookups/milestones', [ProjectTimesheetController::class, 'lookupMilestones'])->name('project_timesheets.lookups.milestones');
        Route::get('/lookups/employees', [ProjectTimesheetController::class, 'lookupEmployees'])->name('project_timesheets.lookups.employees');
    });


    
    // Inventory routes
     Route::prefix('inventory')
          ->name('inventory.') 
          ->group(function () {
          
          Route::prefix('api')->name('api.')->group(function () {
            Route::get('/stores', [StockTransferController::class, 'fetch_stores'])
                ->name('stores');
            
            Route::get('/store-variants', [StockTransferController::class, 'fetch_store_variants'])
                ->name('store_variants');
            
            Route::get('/store-variant-qty', [StockTransferController::class, 'fetch_store_variant_qty'])
                ->name('store_variant_qty');
          });
    
          Route::prefix('flush')
          ->name('flush.') 
          ->group(function () {
              
                Route::get('/', [InventoryFlushController::class, 'index'])
                    ->middleware('permission:inventory.flush.view')
                    ->name('index');
            
                Route::post('/preview', [InventoryFlushController::class, 'preview'])
                    ->middleware('permission:inventory.flush.view')
                    ->name('preview');
            
                Route::post('/', [InventoryFlushController::class, 'flush'])
                    ->middleware('permission:inventory.flush.execute')
                    ->name('run');
          });
         
          Route::prefix('workflow')
          ->name('workflow.') 
          ->group(function () {
                
                Route::get('/', [InventoryWorkflowController::class, 'index'])
                    ->name('index')
                    ->middleware('permission:inventory.stock.workflow.view');
                Route::prefix('sop')
                    ->name('sop.') 
                    ->group(function () {
                        Route::get('/', [InventoryWorkflowController::class, 'sop'])
                            ->name('index')
                            ->middleware('permission:inventory.stock.workflow.sop.export');
                        
                        Route::get('/pdf', [InventoryWorkflowController::class, 'sopPdf'])
                            ->name('pdf')
                            ->middleware('permission:inventory.stock.workflow.sop.export');
               });
  
          });
                    


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

               // Define the correct metrics route separately
               Route::get('/metrics', [ProductController::class, 'get_raw_materials_metrics'])
                    ->name('raw-materials.metrics'); 

               // Define the correct metrics route separately
               Route::post('/', [ProductController::class, 'store_raw_material'])
                    ->name('raw-materials.store'); 
          });
Route::prefix('products')
    ->name('products.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Documentation
        |--------------------------------------------------------------------------
        */
        Route::prefix('docs')->name('docs.')->group(function () {
            Route::get('/workflow', fn() => view('inventory.products.docs.workflow'))->name('workflow');
            Route::get('/privileges', fn() => view('inventory.products.docs.privileges'))->name('privileges');
        });

        /*
        |--------------------------------------------------------------------------
        | Product Variants
        |--------------------------------------------------------------------------
        */
        Route::prefix('variants')->name('variants.')->group(function () {
            Route::get('/fetch', [ProductController::class, 'variantsSelect2'])->name('fetch');

            Route::get('/', [ProductController::class, 'productVariantsIndex'])->name('index');
            Route::get('/datatable', [ProductController::class, 'productVariantsDatatable'])->name('datatable');

            Route::post('/', [ProductController::class, 'storeProductVariant'])->name('store');
            Route::post('/bulk-delete', [ProductController::class, 'bulkDeleteProductVariants'])->name('bulk-delete');

            Route::get('/{product_variant}', [ProductController::class, 'showVariant'])->name('show');
            Route::get('/{product_variant}/edit', [ProductController::class, 'showVariant'])->name('edit');

            Route::put('/{id}', [ProductController::class, 'updateProductVariant'])->name('update');
            Route::delete('/{id}', [ProductController::class, 'destroyProductVariant'])->name('destroy');

            /*
            |--------------------------------------------------------------------------
            | Variant Images
            |--------------------------------------------------------------------------
            */
            Route::get('/{variant}/images', [ProductController::class, 'variantImages'])->name('images.index');
            Route::post('/{variant}/images', [ProductController::class, 'uploadVariantImages'])->name('images.upload');
            Route::post('/images/{id}/primary', [ProductController::class, 'setPrimaryVariantImage'])->name('images.primary');
            Route::delete('/images/{id}', [ProductController::class, 'deleteVariantImage'])->name('images.destroy');
        });

        /*
        |--------------------------------------------------------------------------
        | Per-Product Variant Management
        |--------------------------------------------------------------------------
        */
        Route::get('/{product}/variant-management', [ProductController::class, 'variantsByProductPage'])->name('variants.page');
        Route::get('/{product}/variant-datatable', [ProductController::class, 'productVariantsByProductDatatable'])->name('variants.datatable');

        /*
        |--------------------------------------------------------------------------
        | Product Attribute Matrix / Product-specific attribute fetch
        |--------------------------------------------------------------------------
        */
        Route::get('/{product}/attribute-matrix', [ProductController::class, 'attributeMatrix'])->name('attribute-matrix');
        Route::get('/{product}/attributes', [ProductController::class, 'attributeMatrix'])->name('attributes.matrix');

        /*
        |--------------------------------------------------------------------------
        | Product Attributes
        |--------------------------------------------------------------------------
        */
        Route::prefix('attributes')->name('attributes.')->group(function () {
            Route::get('/', [ProductController::class, 'productAttributesIndex'])->name('index');
            Route::get('/datatable', [ProductController::class, 'productAttributesDatatable'])->name('datatable');
            Route::get('/by-product/{productId}', [ProductController::class, 'getAttributesByProduct'])->name('by-products');

            Route::post('/', [ProductController::class, 'storeProductAttribute'])->name('store');
            Route::put('/{id}', [ProductController::class, 'updateProductAttribute'])->name('update');
            Route::delete('/{id}', [ProductController::class, 'destroyProductAttribute'])->name('destroy');
            Route::post('/bulk-delete', [ProductController::class, 'bulkDeleteProductAttribute'])->name('bulk-delete');

            /*
            |--------------------------------------------------------------------------
            | Attribute Types
            |--------------------------------------------------------------------------
            */
            Route::prefix('types')->name('types.')->group(function () {
                Route::get('/', [ProductController::class, 'productAttributeTypesIndex'])->name('index');
                Route::get('/datatable', [ProductController::class, 'productAttributeTypesDatatable'])->name('datatable');

                Route::post('/', [ProductController::class, 'storeProductAttributeType'])->name('store');
                Route::put('/{id}', [ProductController::class, 'updateProductAttributeType'])->name('update');
                Route::delete('/{id}', [ProductController::class, 'destroyProductAttributeTypes'])->name('destroy');
            });

            /*
            |--------------------------------------------------------------------------
            | Attribute Values
            |--------------------------------------------------------------------------
            */
            Route::prefix('values')->name('values.')->group(function () {
                Route::get('/', [ProductController::class, 'productAttributeValuesIndex'])->name('index');
                Route::get('/datatable', [ProductController::class, 'productAttributeValuesDatatable'])->name('datatable');
                Route::get('/{id}/edit', [ProductController::class, 'editProductAttributeValue'])->name('edit');

                Route::post('/', [ProductController::class, 'storeProductAttributeValue'])->name('store');
                Route::put('/{id}', [ProductController::class, 'updateProductAttributeValue'])->name('update');
                Route::delete('/{id}', [ProductController::class, 'destroyProductAttributeValue'])->name('destroy');
                Route::post('/bulk-delete', [ProductController::class, 'bulkDeleteProductAttributeValues'])->name('bulk-delete');
            });
        });

        /*
        |--------------------------------------------------------------------------
        | Brands
        |--------------------------------------------------------------------------
        */
        Route::prefix('brands')->name('brands.')->group(function () {
            Route::get('/', [BrandController::class, 'index'])->name('list');
            Route::get('/metrics', [BrandController::class, 'metrics'])->name('metrics');
            Route::get('/datatable', [BrandController::class, 'datatable'])->name('datatable');

            Route::post('/store', [BrandController::class, 'store'])->name('store');
            Route::put('/{brand}', [BrandController::class, 'update'])->name('update');
            Route::delete('/{brand}', [BrandController::class, 'destroy'])->name('destroy');
            Route::post('/bulk-delete', [BrandController::class, 'bulkDelete'])->name('bulk-delete');
        });

        /*
        |--------------------------------------------------------------------------
        | Categories
        |--------------------------------------------------------------------------
        */
        Route::prefix('categories')->name('categories.')->group(function () {
            Route::get('/', [ProductCategoryController::class, 'index'])->name('index');
            Route::get('/metrics', [ProductCategoryController::class, 'metrics'])->name('metrics');
            Route::get('/datatable', [ProductCategoryController::class, 'datatable'])->name('datatable');

            Route::post('/store', [ProductCategoryController::class, 'store'])->name('store');
            Route::put('/{category}', [ProductCategoryController::class, 'update'])->name('update');
            Route::delete('/{category_id}', [ProductCategoryController::class, 'destroy'])->name('delete');
            Route::post('/bulk-delete', [ProductCategoryController::class, 'bulkDelete'])->name('bulk-delete');

            Route::get('/{category}', [ProductCategoryController::class, 'show'])->name('show');

            Route::post('/{category}/products/attach', [ProductCategoryController::class, 'attachProducts'])->name('products.attach');
            Route::delete('/{category}/products/{product}', [ProductCategoryController::class, 'detachProduct'])->name('products.detach');
        });

        /*
        |--------------------------------------------------------------------------
        | Manufacturers
        |--------------------------------------------------------------------------
        */
        Route::prefix('manufacturers')->name('manufacturers.')->group(function () {
            Route::get('/', [ManufacturerController::class, 'index'])->name('list');
            Route::get('/metrics', [ManufacturerController::class, 'metrics'])->name('metrics');
            Route::get('/datatable', [ManufacturerController::class, 'datatable'])->name('datatable');

            Route::post('/store', [ManufacturerController::class, 'store'])->name('store');
            Route::put('/{manufacturer}', [ManufacturerController::class, 'update'])->name('update');
            Route::delete('/{manufacturer_id}', [ManufacturerController::class, 'destroy'])->name('delete');
            Route::post('/bulk-delete', [ManufacturerController::class, 'bulkDelete'])->name('bulk-delete');
        });

        /*
        |--------------------------------------------------------------------------
        | Units
        |--------------------------------------------------------------------------
        */
        Route::prefix('units')->name('units.')->group(function () {
            Route::get('/', [UnitController::class, 'index'])->name('index');
            Route::get('/metrics', [UnitController::class, 'metrics'])->name('metrics');
            Route::get('/datatable', [UnitController::class, 'datatable'])->name('datatable');

            Route::post('/store', [UnitController::class, 'store'])->name('store');
            Route::put('/{unit}', [UnitController::class, 'update'])->name('update');
            Route::delete('/{manufacturer_id}', [UnitController::class, 'destroy'])->name('delete');
            Route::post('/bulk-delete', [UnitController::class, 'bulkDelete'])->name('bulk-delete');
        });

        /*
        |--------------------------------------------------------------------------
        | Product Select2
        |--------------------------------------------------------------------------
        */
        Route::get('/select2', [ProductController::class, 'select2'])->name('select2');

        /*
        |--------------------------------------------------------------------------
        | Products
        |--------------------------------------------------------------------------
        */
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('/datatable', [ProductController::class, 'datatable'])->name('datatable');
        Route::get('/metrics', [ProductController::class, 'metrics'])->name('metrics');

        Route::post('/', [ProductController::class, 'store'])->name('store');
        Route::post('/bulk-delete', [ProductController::class, 'bulkDelete'])->name('bulk-delete');

        /*
        |--------------------------------------------------------------------------
        | Product Images
        |--------------------------------------------------------------------------
        */
        Route::get('/{product}/images', [ProductController::class, 'productImages'])->name('images.index');
        Route::post('/{product}/images', [ProductController::class, 'uploadProductImages'])->name('images.upload');
        Route::post('/images/{id}/primary', [ProductController::class, 'setPrimaryProductImage'])->name('images.primary');
        Route::delete('/images/{id}', [ProductController::class, 'deleteProductImage'])->name('images.destroy');
        
        Route::get('/images/{id}/view', [ProductController::class, 'viewProductImage'])
            ->name('images.view');
        
        Route::get('/{product}/images/legacy-view', [ProductController::class, 'viewLegacyProductImage'])
            ->name('images.legacy');

        /*
        |--------------------------------------------------------------------------
        | Product Display / Edit / Update / Delete
        |--------------------------------------------------------------------------
        */
        Route::get('/details/{id}', [ProductController::class, 'details'])->name('details');
        Route::get('/{id}', [ProductController::class, 'show'])->name('show');

        Route::put('/{id}', [ProductController::class, 'update'])->name('update');
        Route::delete('/{id}', [ProductController::class, 'destroy'])->name('destroy');
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
                    Route::get('{return}/json','json')->name('json');
                    Route::post('{return}/approve','approve')->name('approve');
                    Route::post('{return}/post',   'post')->name('post');
                    Route::get('/select2/customers','select2Customers')->name('select2.customers');
                    Route::get('/select2/deliveries', 'select2SalesDeliveries')->name('select2.deliveries');
               });

               Route::controller(SupplierReturnController::class)
               ->prefix('supplier')
               ->name('supplier.')
               ->group(function(){
                    Route::get('/','index')->name('index');
                    Route::get('datatable','datatable')->name('datatable');
                    Route::post('/','store')->name('store');
                    Route::get('{return}','edit')->name('edit');
                    Route::put('{return}','update')->name('update');
                    Route::delete('{return}','destroy')->name('destroy');
                    Route::post('{return}/approve','approve')->name('approve');
                    Route::post('{return}/post','post')->name('post');
               });
          });

          Route::prefix('/stock')
               ->name('stock.')
               ->controller(StockLevelController::class)
               ->group(function () {
                                    
                    Route::prefix('dashboard')->name('dashboard.')->group(function () {
                        Route::get('/', [StockDashboardController::class, 'index'])
                                ->name('dashboard')
                                ->middleware('permission:inventory.stock.dashboard.view');
                        
                            Route::get('/data', [StockDashboardController::class, 'data'])
                                ->name('data')
                                ->middleware('permission:inventory.stock.dashboard.view');
                        
                            Route::get('/export', [StockDashboardController::class, 'export'])
                                ->name('export')
                                ->middleware('permission:inventory.stock.dashboard.export');
                    });
                    Route::prefix('dashboard')->name('dashboard.')->group(function () {
                         #routes/web.php  (within the inventory group / auth middleware)
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
                         Route::prefix('dashboard')->name('dashboard.')->group(function () {
                            Route::get('/', [StockLevelsDashboardController::class, 'index'])
                                ->name('index');
                        
                            Route::get('/data', [StockLevelsDashboardController::class, 'data'])
                                ->name('data');
                         });
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
                             
                              Route::prefix('/dashboard')->name('dashboard.')->group(function () {
                                  Route::get('/', [StockTransferDashboardController::class,'index'])->name('index');
                                  Route::get('/data', [StockTransferDashboardController::class,'data'])->name('data');
                                  Route::get('/export', [StockTransferDashboardController::class,'export'])->name('export');
                              });
                              
                              Route::get('/fetch-variants', [StockTransferController::class,'fetch_variants'])->name('fetch-variants');
                              Route::put('/{transfer}', [StockTransferController::class, 'update'])->name('update');
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
              
               Route::prefix('analytics')->name('analytics.')->group(function () {
                    // Analytics page
                    Route::get('/', [StockEntryAnalyticsController::class, 'index'])
                        ->name('index')
                        ->middleware('permission:inventory.stock_entries.analytics.view');
                
                    Route::get('/data', [StockEntryAnalyticsController::class, 'data'])
                        ->name('data')
                        ->middleware('permission:inventory.stock_entries.analytics.view');
                
                    Route::get('/export', [StockEntryAnalyticsController::class, 'export'])
                        ->name('export');

               });
               

               Route::prefix('transactions')->name('transactions.')->group(function () {
                     /* Ledger */
                    Route::get('/', [StockController::class,'stockTransactionsIndex'])->name('index');

                    Route::post('/', [StockController::class,'storeStockTransaction'])->name('store');

                    Route::get('/datatable', [StockController::class,'stockTransactionsDatatable'])->name('datatable');

                    Route::get('/bulk-delete', [StockController::class,'bulkDeleteStockTransactions'])->name('bulk-delete');

               });

               // Stock Entries
               Route::get('/', [StockController::class, 'index'])->name('index');
               Route::get('/datatable', [StockController::class, 'datatable'])->name('datatable');
               Route::post('/', [StockController::class, 'store'])->name('store');
               Route::get('/export', [StockController::class, 'export'])
               ->name('export');
               Route::post('/{id}/approve', [StockController::class, 'approve']);
               Route::post('/{id}/post',    [StockController::class, 'post']);
               Route::post('/{id}/unpost',  [StockController::class, 'unpost']); // optional

               Route::get('/{id}', [StockController::class, 'show'])->name('show');
               Route::put('/{id}', [StockController::class, 'update'])->name('update');
               Route::delete('/{id}', [StockController::class, 'destroy'])->name('destroy');
        
               // Stock Entry Lines (NESTED under entry)
               Route::get('/{entry}/lines/datatable', [StockController::class, 'stockEntryLineDatatable'])
                   ->name('lines.datatable');
        
               Route::post('/{entry}/lines', [StockController::class, 'storeStockEntryLine'])
                   ->name('lines.store');
        
               Route::put('/{entry}/lines/{line}', [StockController::class, 'updateStockEntryLine'])
                  ->name('stock_entries.lines.update');
        
               Route::delete('/{entry}/lines/{line}', [StockController::class, 'destroyStockEntryLine'])
                   ->name('lines.destroy');
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
     
     // Settings Groups (optional manager)
    Route::prefix('setting-groups')->name('setting_groups.')->group(function () {
        Route::get('/',               [\App\Http\Controllers\SettingGroupController::class, 'index'])->name('index');
        Route::get('/datatable',      [\App\Http\Controllers\SettingGroupController::class, 'datatable'])->name('datatable');
        Route::post('/',              [\App\Http\Controllers\SettingGroupController::class, 'store'])->name('store');
        Route::put('/{group}',        [\App\Http\Controllers\SettingGroupController::class, 'update'])->name('update');
        Route::delete('/{group}',     [\App\Http\Controllers\SettingGroupController::class, 'destroy'])->name('destroy');
    });
    
        Route::prefix('/suppliers')->name('suppliers.')->group(function () {

            /* =======================
             * EXPORTS (SUPPLIERS)
             * ======================= */
            Route::get('export/csv',  [SupplierController::class, 'exportCsv'])->name('export.csv');
            Route::get('export/pdf',  [SupplierController::class, 'exportPdf'])->name('export.pdf');
            Route::get('export/excel',[SupplierController::class, 'exportExcel'])->name('export.excel');
        
            Route::get('/countries/select2', [SupplierController::class, 'countriesSelect2'])->name('countries.select2');
            Route::get('/states/select2', [SupplierController::class, 'statesSelect2'])->name('states.select2');
            Route::get('/cities/select2', [SupplierController::class, 'citiesSelect2'])->name('cities.select2');

            /* =======================
             * GLOBAL PAGES (WHOLE SYSTEM)
             * - Addresses: global view/search
             * - Contacts:  global view/search
             * ======================= */
        
            // ✅ Global Addresses page + datatable + CRUD + exports
            Route::prefix('addresses')->name('addresses.')->group(function () {
                Route::get('/',          [SupplierController::class, 'suppliersAddressesIndex'])->name('index');
                Route::get('datatable',  [SupplierController::class, 'suppliersAddressesDatatable'])->name('datatable');
                Route::post('store',     [SupplierController::class, 'storeSuppliersAddress'])->name('store');
                Route::put('{id}',       [SupplierController::class, 'updateSuppliersAddress'])->name('update');
                Route::delete('{id}',    [SupplierController::class, 'deleteSuppliersAddress'])->name('destroy');
                Route::post('bulk-delete',[SupplierController::class, 'bulkDeleteSuppliersAddresses'])->name('bulk-delete');
        
                // optional metrics (use a dedicated method if you want separate counts)
                Route::get('metrics',    [SupplierController::class, 'metrics'])->name('metrics');
        
                // exports (addresses)
                Route::get('export/excel',[SupplierController::class, 'exportSupplierAddressesExcel'])->name('export.excel');
                Route::get('export/csv',  [SupplierController::class, 'exportSupplierAddressesCsv'])->name('export.csv');
                Route::get('export/pdf',  [SupplierController::class, 'exportSupplierAddressesPdf'])->name('export.pdf');
            });
        
            // ✅ Global Contacts page + datatable + CRUD + exports
            Route::prefix('contacts')->name('contacts.')->group(function () {
                Route::get('/',          [SupplierController::class, 'suppliersContactsIndex'])->name('index');
                Route::get('datatable',  [SupplierController::class, 'suppliersContactsDatatable'])->name('datatable');
                Route::post('store',     [SupplierController::class, 'storeSuppliersContact'])->name('store');
                Route::put('{id}',       [SupplierController::class, 'updateSuppliersContact'])->name('update');
                Route::delete('{id}',    [SupplierController::class, 'deleteSuppliersContact'])->name('destroy');
                Route::post('bulk-delete',[SupplierController::class, 'bulkDeleteSuppliersContacts'])->name('bulk-delete');
        
                // optional metrics (use a dedicated method if you want separate counts)
                Route::get('metrics',    [SupplierController::class, 'metrics'])->name('metrics');
        
                // exports (contacts)
                Route::get('export/excel',[SupplierController::class, 'exportSupplierContactsExcel'])->name('export.excel');
                Route::get('export/csv',  [SupplierController::class, 'exportSupplierContactsCsv'])->name('export.csv');
                Route::get('export/pdf',  [SupplierController::class, 'exportSupplierContactsPdf'])->name('export.pdf');
            });
        
        
            // ✅ Per-supplier datatables for tabs (filter by supplier_id internally)
            Route::get('{supplier}/contacts/datatable',   [SupplierController::class, 'showContactsDatatable'])->name('show.contacts.datatable');
            Route::get('{supplier}/addresses/datatable',  [SupplierController::class, 'showAddressesDatatable'])->name('show.addresses.datatable');
        
            // ✅ Per-supplier CRUD (optional — you can reuse global endpoints too)
            Route::post('{supplier}/contacts/store',      [SupplierController::class, 'storeSupplierContactForSupplier'])->name('show.contacts.store');
            Route::put('{supplier}/contacts/{id}',        [SupplierController::class, 'updateSupplierContactForSupplier'])->name('show.contacts.update');
            Route::delete('{supplier}/contacts/{id}',     [SupplierController::class, 'deleteSupplierContactForSupplier'])->name('show.contacts.destroy');
        
            Route::post('{supplier}/addresses/store',     [SupplierController::class, 'storeSupplierAddressForSupplier'])->name('show.addresses.store');
            Route::put('{supplier}/addresses/{id}',       [SupplierController::class, 'updateSupplierAddressForSupplier'])->name('show.addresses.update');
            Route::delete('{supplier}/addresses/{id}',    [SupplierController::class, 'deleteSupplierAddressForSupplier'])->name('show.addresses.destroy');
        
            /* =======================
             * SUPPLIERS (CORE CRUD)
             * ======================= */
            Route::get('select2',        [SupplierController::class, 'select2'])->name('select2');
            Route::get('/',              [SupplierController::class, 'index'])->name('index');
            Route::get('datatable',      [SupplierController::class, 'datatable'])->name('datatable');
            Route::post('store',         [SupplierController::class, 'store'])->name('store');
            Route::put('{id}',           [SupplierController::class, 'update'])->name('update');
            Route::delete('{id}',        [SupplierController::class, 'destroy'])->name('destroy');
            Route::post('bulk-delete',   [SupplierController::class, 'bulkDelete'])->name('bulk-delete');
            Route::get('metrics',        [SupplierController::class, 'metrics'])->name('metrics');
            Route::get('{supplier}', [SupplierController::class, 'show'])->name('show');
        });

});
