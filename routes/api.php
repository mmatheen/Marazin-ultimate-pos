<?php

use Illuminate\Http\Request;
use App\Models\VariationTitle;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\{
  BrandController,
  CartController,
  CustomerGroupController,
  ExpenseController,
  ExpenseParentCategoryController,
  ExpenseSubCategoryController,
  LocationController,
  MainCategoryController,
  OpeningStockController,
  PaymentController,
  PurchaseController,
  PurchaseReturnController,
  RoleAndPermissionController,
  RoleController,
  SaleController,
  SaleReturnController,
  SalesReturnController,
  SellingPriceGroupController,
  SellController,
  StockAdjustmentController,
  StockTransferController,
  SubCategoryController,
  SupplierController,
  UnitController,
  VariationController,
  VariationTitleController,
  WarrantyController
};

use App\Http\Controllers\Api\{
  SalesRepController,
  RouteController,
  CityController,
  RouteCityController,
  SalesRepTargetController,
  VehicleTrackingController,
  ProductController,
  CustomerController,
  SaleController as ApiSaleController
};

/*
|--------------------------------------------------------------------------
| API Routes Structure
|--------------------------------------------------------------------------
| 1. Public Routes (No Authentication Required)
| 2. Authenticated Routes (auth:sanctum middleware)
| 3. Web-based API Routes (web middleware)
|--------------------------------------------------------------------------
*/

// ============================================================================
// 1. PUBLIC ROUTES (No Authentication Required)
// ============================================================================

// Authentication Routes
Route::post('/login', [AuthenticatedSessionController::class, 'store']);

// Authentication Routes
Route::post('/login', [AuthenticatedSessionController::class, 'store']);

// Public Product Routes (might be needed for mobile apps without auth)
Route::get('/get-brand', [BrandController::class, 'brandDropdown']);

// ============================================================================
// 2. AUTHENTICATED ROUTES (auth:sanctum middleware)
// ============================================================================

Route::middleware('auth:sanctum')->group(function () {

    // User Info Route - Returns authenticated user with roles and permissions
    Route::get('/user', function (Request $request) {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Load relationships
        $user->load(['roles', 'locations', 'permissions']);

        // Get role information
        $role = $user->roles->first();
        $roleName = $role?->name ?? null;
        $roleKey = $role?->key ?? null;

        return response()->json([
            'status' => 'success',
            'user' => [
                'id' => $user->id,
                'user_name' => $user->user_name,
                'full_name' => $user->full_name ?? null,
                'name_title' => $user->name_title ?? null,
                'email' => $user->email,
                'role' => $roleName,
                'role_key' => $roleKey,
                'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                'can_bypass_location_scope' => $role?->bypass_location_scope ?? false,
                'is_master_super_admin' => $roleName === 'Master Super Admin',
                'is_super_admin' => $roleKey === 'super_admin',
                'locations' => $user->locations->map(function ($loc) {
                    return [
                        'id' => $loc->id,
                        'name' => $loc->name,
                        'code' => $loc->code ?? null,
                    ];
                })->toArray(),
            ]
        ], 200);
    });

    // ========================================
    // MASTER DATA MANAGEMENT
    // ========================================

    // Brand Management
    Route::get('/brand-edit/{id}', [BrandController::class, 'edit']);
    Route::get('/brand-get-all', [BrandController::class, 'index']);
    Route::post('/brand-store', [BrandController::class, 'store'])->name('brand-store');
    Route::post('/brand-update/{id}', [BrandController::class, 'update']);
    Route::delete('/brand-delete/{id}', [BrandController::class, 'destroy']);

    // Main Category Management
    Route::get('/main-category-edit/{id}', [MainCategoryController::class, 'edit']);
    Route::get('/main-category-get-all', [MainCategoryController::class, 'index']);
    Route::post('/main-category-store', [MainCategoryController::class, 'store'])->name('main-category-store');
    Route::post('/main-category-update/{id}', [MainCategoryController::class, 'update']);
    Route::delete('/main-category-delete/{id}', [MainCategoryController::class, 'destroy']);

    // Sub Category Management
    Route::get('/sub-category-edit/{id}', [SubCategoryController::class, 'edit']);
    Route::get('/sub-category-get-all', [SubCategoryController::class, 'index']);
    Route::post('/sub-category-store', [SubCategoryController::class, 'store'])->name('sub-category-store');
    Route::post('/sub-category-update/{id}', [SubCategoryController::class, 'update']);
    Route::delete('/sub-category-delete/{id}', [SubCategoryController::class, 'destroy']);

    // Unit Management
    Route::get('/unit', [UnitController::class, 'unit'])->name('brand');
    Route::get('/unit-edit/{id}', [UnitController::class, 'edit']);
    Route::get('/unit-get-all', [UnitController::class, 'index']);
    Route::post('/unit-store', [UnitController::class, 'store']);
    Route::post('/unit-update/{id}', [UnitController::class, 'update']);
    Route::delete('/unit-delete/{id}', [UnitController::class, 'destroy']);

    // Warranty Management
    Route::post('/warranty-store', [WarrantyController::class, 'store']);
    Route::get('/warranty-get-all', [WarrantyController::class, 'index']);
    Route::get('/warranty-edit/{id}', [WarrantyController::class, 'edit']);
    Route::put('/warranty-update/{id}', [WarrantyController::class, 'update']);
    Route::delete('/warranty-delete/{id}', [WarrantyController::class, 'destroy']);

    // Selling Price Group Management
    Route::get('/selling-price-group-edit/{id}', [SellingPriceGroupController::class, 'edit']);
    Route::get('/selling-price-group-get-all', [SellingPriceGroupController::class, 'index']);
    Route::post('/selling-price-group-store', [SellingPriceGroupController::class, 'store'])->name('selling-price-group-store');
    Route::post('/selling-price-group-update/{id}', [SellingPriceGroupController::class, 'update']);
    Route::delete('/selling-price-group-delete/{id}', [SellingPriceGroupController::class, 'destroy']);

    // Variation Title Management
    Route::get('/variation-title-edit/{id}', [VariationTitleController::class, 'edit']);
    Route::get('/variation-title-get-all', [VariationTitleController::class, 'index']);
    Route::post('/variation-title-store', [VariationTitleController::class, 'store'])->name('variation-title-store');
    Route::post('/variation-title-update/{id}', [VariationTitleController::class, 'update']);
    Route::delete('/variation-title-delete/{id}', [VariationTitleController::class, 'destroy']);

    // Variation Management
    Route::get('/variation-edit/{id}', [VariationController::class, 'edit']);
    Route::get('/variation-get-all', [VariationController::class, 'index']);
    Route::post('/variation-store', [VariationController::class, 'store'])->name('variation-title-store');
    Route::post('/variation-update/{id}', [VariationController::class, 'update']);
    Route::delete('/variation-delete/{id}', [VariationController::class, 'destroy']);

    // ========================================
    // LOCATION MANAGEMENT
    // ========================================

    Route::get('/location', [LocationController::class, 'location']);
    Route::get('/location-edit/{id}', [LocationController::class, 'edit']);
    Route::get('/location-get-all', [LocationController::class, 'index']);
    Route::post('/location-store', [LocationController::class, 'store']);
    Route::post('/location-update/{id}', [LocationController::class, 'update']);
    Route::delete('/location-delete/{id}', [LocationController::class, 'destroy']);

    // ========================================
    // CUSTOMER MANAGEMENT
    // ========================================

    // Customer Group Management
    Route::get('/customer-group-edit/{id}', [CustomerGroupController::class, 'edit']);
    Route::get('/customer-group-get-all', [CustomerGroupController::class, 'index']);
    Route::post('/customer-group-store', [CustomerGroupController::class, 'store'])->name('customer-group-store');
    Route::post('/customer-group-update/{id}', [CustomerGroupController::class, 'update']);
    Route::delete('/customer-group-delete/{id}', [CustomerGroupController::class, 'destroy']);

    // Customer Management
    Route::get('/customer-get-all', [CustomerController::class, 'index']);
    Route::get('/customer-edit/{id}', [CustomerController::class, 'edit']);
    Route::get('/customer-get-by-route/{routeId}', [CustomerController::class, 'getCustomersByRoute']);
    Route::post('/customers/filter-by-cities', [CustomerController::class, 'filterByCities']);
    Route::post('/customer-store', [CustomerController::class, 'store']);
    Route::post('/customer-update/{id}', [CustomerController::class, 'update']);
    Route::delete('/customer-delete/{id}', [CustomerController::class, 'destroy']);
    Route::get('/customer-get-by-id/{id}', [CustomerController::class, 'show']);

    // ========================================
    // SUPPLIER MANAGEMENT
    // ========================================

    Route::get('/supplier-edit/{id}', [SupplierController::class, 'edit']);
    Route::get('/supplier-get-all', [SupplierController::class, 'index']);
    Route::post('/supplier-store', [SupplierController::class, 'store']);
    Route::post('/supplier-update/{id}', [SupplierController::class, 'update']);
    Route::delete('/supplier-delete/{id}', [SupplierController::class, 'destroy']);

    // ========================================
    // PRODUCT MANAGEMENT
    // ========================================

    // Product Basic Operations
    Route::get('/products/stocks', [ProductController::class, 'getAllProductStocks']);
    Route::delete('/delete-product/{id}', [ProductController::class, 'destroy']); // Safe delete with validation
    Route::post('/toggle-product-status/{id}', [ProductController::class, 'toggleStatus']);
    Route::get('/initial-product-details', [ProductController::class, 'initialProductDetails'])->name('product-details');
    Route::get('/product-get-details/{id}', [ProductController::class, 'getProductDetails']);
    Route::get('/products/stock-history/{id}', [ProductController::class, 'getStockHistory'])->name('productStockHistory');
    Route::get('/products/stocks/autocomplete', [ProductController::class, 'autocompleteStock']);
    Route::post('/product/store', [ProductController::class, 'storeOrUpdate']);
    Route::post('/product/update/{id}', [ProductController::class, 'storeOrUpdate']);
    Route::post('/product/check-sku', [ProductController::class, 'checkSkuUniqueness']); // Real-time SKU validation
    Route::get('/get-last-product', [ProductController::class, 'getLastProduct']);

    // Product Category Relations
    Route::get('/product-get-by-category/{categoryId}', [ProductController::class, 'getProductsByCategory']);
    Route::get('/sub_category-details-get-by-main-category-id/{main_category_id}', [ProductController::class, 'showSubCategoryDetailsUsingByMainCategoryId'])->name('sub_category-details-get-by-main-category-id');

    // Product Import/Export
    Route::get('/import-product', [ProductController::class, 'importProduct'])->name('import-product');
    Route::post('/import-product-excel-store', [ProductController::class, 'importProductStore'])->name('import-product-excel-store');
    Route::get('/excel-product-blank-template-export', [ProductController::class, 'exportBlankTemplate'])->name('excel-product-blank-template-export');
    Route::get('/products/export-template', [ProductController::class, 'exportProducts'])->name('products.export-template');

    // Opening Stock Management
    Route::get('/opening-stock/{productId}', [ProductController::class, 'showOpeningStock'])->name('opening.stock');
    Route::get('/edit-opening-stock/{productId}', [ProductController::class, 'editOpeningStock'])->name('product.editOpeningStock');
    Route::post('/opening-stock/{productId}', [ProductController::class, 'storeOrUpdateOpeningStock']);
    Route::delete('/opening-stock/{productId}', [ProductController::class, 'deleteOpeningStock'])->name('api.opening.stock.delete');
    Route::get('/opening-stocks-get-all', [ProductController::class, 'OpeningStockGetAll']);

    // Import Opening Stock Management
    Route::get('/import-opening-stock', [OpeningStockController::class, 'importOpeningStock'])->name('importOpeningStock');
    Route::get('/import-opening-stock-edit/{id}', [OpeningStockController::class, 'edit']);
    Route::get('/import-opening-stock-get-all', [OpeningStockController::class, 'index']);
    Route::post('/import-opening-stock-store', [OpeningStockController::class, 'store']);
    Route::post('/import-opening-stock-update/{id}', [OpeningStockController::class, 'update']);
    Route::delete('/import-opening-stock-delete/{id}', [OpeningStockController::class, 'destroy']);

    // Product Notifications
    Route::get('/notifications', [ProductController::class, 'getNotifications']);
    Route::post('/notifications/seen', [ProductController::class, 'markNotificationsAsSeen']);

    // API IMEI Management (using API ProductController)
    Route::post('/imei/save-or-update', [ProductController::class, 'saveOrUpdateImei']);
    Route::post('/imei/update', [ProductController::class, 'updateSingleImei']);
    Route::post('/imei/delete', [ProductController::class, 'deleteImei']);
    Route::get('/imei/get/{productId}', [ProductController::class, 'getImeis'])->name('api.getImeis');

    // Product Miscellaneous
    Route::post('/save-changes', [ProductController::class, 'saveChanges']);
    Route::post('/get-product-locations', [ProductController::class, 'getProductLocations']);
    Route::post('/apply-discount', [ProductController::class, 'applyDiscount'])->name('products.applyDiscount');

      // IMEI Management (moved to web middleware for session-based auth)
    Route::post('/save-or-update-imei', [ProductController::class, 'saveOrUpdateImei']);
    Route::post('/update-imei', [ProductController::class, 'updateSingleImei']);
    Route::post('/delete-imei', [ProductController::class, 'deleteImei']);
    Route::get('/get-imeis/{productId}', [ProductController::class, 'getImeis'])->name('getImeis');

    // ========================================
    // SALES MANAGEMENT
    // ========================================
    Route::get('sales', [SaleController::class, 'index'])->name('sales.index');
    Route::get('/sales/paginated', [SaleController::class, 'getDataTableSales'])->name('sales.paginated');
    Route::post('/sales/store', [SaleController::class, 'storeOrUpdate']);
    Route::post('/sales/update/{id}', [SaleController::class, 'storeOrUpdate']);
    Route::post('/sales/clear-cache', [SaleController::class, 'clearSalesCache']);
    Route::get('/sales/{invoiceNo}', [SaleController::class, 'getSaleByInvoiceNo']);
    Route::get('/search/sales', [SaleController::class, 'searchSales']);
    Route::get('sales_details/{id}', [SaleController::class, 'salesDetails']);
    Route::get('/sales/edit/{id}', [SaleController::class, 'editSale'])->name('sales.edit');
    Route::delete('/sales/delete/{id}', [SaleController::class, 'destroy'])->name('sales.destroy');
    Route::get('/sales/suspended', [SaleController::class, 'fetchSuspendedSales']);
    Route::get('/pos/sales/edit/{id}', [SaleController::class, 'show']);
    Route::delete('/sales/delete-suspended/{id}', [SaleController::class, 'deleteSuspendedSale']);
    Route::get('/daily-sales-report', [SaleController::class, 'dailyReport']);

    // Sale Returns Management
    Route::get('sale-returns', [SaleReturnController::class, 'getAllSaleReturns']);
    Route::get('sale-return/add', [SaleReturnController::class, 'addSaleReturn'])->name('sale-return/add');
    Route::post('sale-return/store', [SaleReturnController::class, 'storeOrUpdate']);
    Route::put('sale-return/update/{id}', [SaleReturnController::class, 'storeOrUpdate']);
    Route::get('sale-return/edit/{id}', [SaleReturnController::class, 'editSaleReturn']);

    // ========================================
    // PURCHASE MANAGEMENT
    // ========================================

    Route::post('/purchases/store', [PurchaseController::class, 'storeOrUpdate']);
    Route::post('/purchases/update/{id}', [PurchaseController::class, 'storeOrUpdate']);
    Route::get('/purchase/edit/{id}', [PurchaseController::class, 'editPurchase']);
    Route::get('/get-all-purchases', [PurchaseController::class, 'getAllPurchase']);
    Route::get('/get-all-purchases-product/{id }', [PurchaseController::class, 'getAllPurchaseProduct']);
    Route::get('/purchase-products-by-supplier/{supplierId}', [PurchaseController::class, 'getPurchaseProductsBySupplier']);

    // Purchase Returns Management
    Route::get('/purchase-returns/get-product-details/{purchaseId}/{supplierId}', [PurchaseReturnController::class, 'getProductDetails']);
    Route::get('purchase_returns', [PurchaseReturnController::class, 'getAllPurchaseReturns']);
    Route::get('purchase_returns/{id}', [PurchaseReturnController::class, 'getPurchaseReturns']);
    Route::get('purchase_return/edit/{id}', [PurchaseReturnController::class, 'edit']);
    Route::post('/purchase-return/store', [PurchaseReturnController::class, 'storeOrUpdate']);
    Route::post('/purchase-return/update/{id}', [PurchaseReturnController::class, 'storeOrUpdate']);

    // ========================================
    // STOCK MANAGEMENT
    // ========================================

    // Stock Transfer Management
    Route::get('/list-stock-transfer', [StockTransferController::class, 'stockTransfer'])->name('list-stock-transfer');
    Route::get('/add-stock-transfer', [StockTransferController::class, 'addStockTransfer'])->name('add-stock-transfer');
    Route::post('/stock-transfer/store', [StockTransferController::class, 'storeOrUpdate']);
    Route::put('/stock-transfer/update/{id}', [StockTransferController::class, 'storeOrUpdate']);
    Route::get('/edit-stock-transfer/{id}', [StockTransferController::class, 'edit']);

    // Stock Adjustment Management
    Route::get('/stock-adjustments', [StockAdjustmentController::class, 'index'])->name('stock-adjustments.index');
    Route::get('/edit-stock-adjustment/{id}', [StockAdjustmentController::class, 'edit'])->name('stock-adjustments.edit');
    Route::get('/stock-adjustments/{id}', [StockAdjustmentController::class, 'show'])->name('stock-adjustments.show');
    Route::post('/stock-adjustment/store', [StockAdjustmentController::class, 'storeOrUpdate']);
    Route::put('/stock-adjustment/update/{id}', [StockAdjustmentController::class, 'storeOrUpdate']);

    // ========================================
    // EXPENSE MANAGEMENT
    // ========================================

    // Expense Parent Category Management
    Route::get('/expense-parent-catergory-edit/{id}', [ExpenseParentCategoryController::class, 'edit']);
    Route::get('/expense-parent-catergory-get-all', [ExpenseParentCategoryController::class, 'index']);
    Route::post('/expense-parent-catergory-store', [ExpenseParentCategoryController::class, 'store'])->name('expense-parent-catergory-store');
    Route::post('/expense-parent-catergory-update/{id}', [ExpenseParentCategoryController::class, 'update']);
    Route::delete('/expense-parent-catergory-delete/{id}', [ExpenseParentCategoryController::class, 'destroy']);

    // Expense Sub Category Management
    Route::get('/sub-expense-category-edit/{id}', [ExpenseSubCategoryController::class, 'edit']);
    Route::get('/sub-expense-category-get-all', [ExpenseSubCategoryController::class, 'index']);
    Route::post('/sub-expense-category-store', [ExpenseSubCategoryController::class, 'store'])->name('sub-expense-category-store');
    Route::post('/sub-expense-category-update/{id}', [ExpenseSubCategoryController::class, 'update']);
    Route::delete('/sub-expense-category-delete/{id}', [ExpenseSubCategoryController::class, 'destroy']);

    // Expense Management
    Route::get('/expense-edit/{id}', [ExpenseController::class, 'edit']);
    Route::get('/expense-show/{id}', [ExpenseController::class, 'show']);
    Route::get('/expense-get-all', [ExpenseController::class, 'index']);
    Route::post('/expense-store', [ExpenseController::class, 'store'])->name('expense-store');
    Route::post('/expense-update/{id}', [ExpenseController::class, 'update']);
    Route::delete('/expense-delete/{id}', [ExpenseController::class, 'destroy']);
    Route::get('/expense-sub-categories/{parentId}', [ExpenseController::class, 'getSubCategories']);
    Route::get('/expense-reports', [ExpenseController::class, 'reports']);

    // ========================================
    // PAYMENT MANAGEMENT
    // ========================================

    Route::get('payments', [PaymentController::class, 'index']);
    Route::post('payments', [PaymentController::class, 'storeOrUpdate']);
    Route::get('payments/{payment}', [PaymentController::class, 'show']);
    Route::put('payments/{payment}', [PaymentController::class, 'storeOrUpdate']);
    Route::delete('payments/{payment}', [PaymentController::class, 'destroy']);
    Route::post('/supplier-payment', [PaymentController::class, 'handleSupplierPayment']);
    Route::post('/submit-bulk-payment', [PaymentController::class, 'submitBulkPayment']);

    // ========================================
    // USER & ROLE MANAGEMENT
    // ========================================

    // Role Management
    Route::get('/role', [RoleController::class, 'role'])->name('role');
    Route::get('/role-edit/{id}', [RoleController::class, 'edit']);
    Route::get('/role-get-all', [RoleController::class, 'index']);
    Route::post('/role-store', [RoleController::class, 'store'])->name('role-store');
    Route::post('/role-update/{id}', [RoleController::class, 'update']);
    Route::delete('/role-delete/{id}', [RoleController::class, 'destroy']);
    Route::get('/user-select-box-dropdown', [RoleController::class, 'SelectRoleNameDropdown'])->name('role.dropdown');

    // Role & Permission Management
    Route::get('/group-role-and-permission-view', [RoleAndPermissionController::class, 'groupRoleAndPermissionView'])->name('group-role-and-permission-view');
    Route::get('/group-role-and-permission', [RoleAndPermissionController::class, 'groupRoleAndPermission'])->name('group-role-and-permission');
    Route::get('/role-and-permission-edit/{role_id}', [RoleAndPermissionController::class, 'edit']);
    Route::post('/role-and-permission-store', [RoleAndPermissionController::class, 'store'])->name('role-and-permission-store');
    Route::post('/role-and-permission-update/{role_id}', [RoleAndPermissionController::class, 'update'])->name('group-and-permission-update');
    Route::get('/role-and-permission-all', [RoleAndPermissionController::class, 'groupRoleAndPermissionList'])->name('role-and-permission-all');
    Route::delete('/role-and-permission-delete/{role_id}', [RoleAndPermissionController::class, 'destroy']);

    // ========================================
    // SALES REP & ROUTING MANAGEMENT
    // ========================================

    // Sales Rep Management
    Route::apiResource('sales-reps', SalesRepController::class);
    Route::get('/sales-reps/available-users', [SalesRepController::class, 'getAvailableUsers']);
    Route::get('/sales-reps/user-locations/{userId}', [SalesRepController::class, 'getUserAccessibleLocations']);
    Route::get('/sales-reps/routes/available', [SalesRepController::class, 'getAvailableRoutes']);
    Route::get('sales-reps/available-routes', [SalesRepController::class, 'getAvailableRoutes']);
    Route::post('/sales-reps/assign-locations', [SalesRepController::class, 'assignUserToLocations']);
    Route::get('/sales-rep/my-assignments', [SalesRepController::class, 'getMyAssignments']);

    // Status Management Routes
    Route::post('/sales-reps/update-statuses', [SalesRepController::class, 'updateAllStatusesByDate']);
    Route::get('/sales-reps/expiring-soon', [SalesRepController::class, 'getExpiringSoon']);
    Route::get('/sales-reps/status-statistics', [SalesRepController::class, 'getStatusStatistics']);
    Route::put('/sales-reps/{id}/cancel', [SalesRepController::class, 'cancelAssignment']);
    Route::put('/sales-reps/{id}/reactivate', [SalesRepController::class, 'reactivateAssignment']);

    // Route Management
    Route::apiResource('routes', RouteController::class);
    Route::get('/routes/cities/available', [RouteController::class, 'getAvailableCities']);
    Route::get('/routes/{routeId}/cities', [RouteController::class, 'getRouteCities']);
    Route::post('/routes/{routeId}/cities/add', [RouteController::class, 'addCities']);
    Route::delete('/routes/{routeId}/cities/remove', [RouteController::class, 'removeCities']);
    Route::put('/routes/{routeId}/status', [RouteController::class, 'changeStatus']);

    // City Management
    Route::apiResource('cities', CityController::class);

    // Route-City Management
    Route::apiResource('route-cities', RouteCityController::class);
    Route::get('/route-cities/cities/all', [RouteCityController::class, 'getAllCities']);
    Route::get('/route-cities/routes/all', [RouteCityController::class, 'getAllRoutes']);

    // Sales Rep Target Management
    Route::apiResource('sales-rep-targets', SalesRepTargetController::class);

    // Customer Previous Price History
    Route::get('/customer-previous-price', [SaleController::class, 'getCustomerPreviousPrice']);

});

// ============================================================================
// 3. WEB-BASED API ROUTES (web middleware for session auth)
// ============================================================================



