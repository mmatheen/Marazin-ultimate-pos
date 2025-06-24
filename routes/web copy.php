<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\{
    SaleController,
    UnitController,
    UserController,
    BrandController,
    ContactController,
    ProductController,
    ProfileController,
    CustomerController,
    LocationController,
    PurchaseController,
    SupplierController,
    WarrantyController,
    VariationController,
    DashboardController,
    PrintLabelController,
    SubCategoryController,
    MainCategoryController,
    OpeningStockController,
    CustomerGroupController,
    StockTransferController,
    AuthenticationController,
    PurchaseReturnController,
    VariationTitleController,
    SellingPriceGroupController,
    ExpenseSubCategoryController,
    ExpenseParentCategoryController,
    SaleReturnController,
    SalesCommissionAgentsController,
    RoleController,
    RoleAndPermissionController,
    StockAdjustmentController,
    CartController,
    PaymentController,
    DiscountController,
    ReportController,
};


function set_active($route)
{
    if (is_array($route)) {
        return in_array(Request::path(), $route) ? 'active' : '';
    }
    return Request::path() == $route ? 'active' : '';
}

Route::get('/testing', function () {
    $role = Auth::user()->location_id;
    dd($role);
});

Route::get('/dashboard', function () {
    return view('includes.dashboards.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__ . '/auth.php';

Route::middleware(['auth', 'check.session'])->group(function () {

    Route::group(['middleware' => function ($request, $next) {
        $role = Auth::user()->role_name ?? null;
        if ($role) {
            $request->route()->middleware("role:$role");
        }
        return $next($request);
    }], function () {



        // -------------------- User Routes --------------------
        // List Users
        Route::get('/user', [UserController::class, 'user'])->name('user');
        // Edit User
        Route::get('/user-edit/{id}', [UserController::class, 'edit']);
        // Get All Users
        Route::get('/user-get-all', [UserController::class, 'index']);
        // Store New User
        Route::post('/user-store', [UserController::class, 'store']);
        // Update User
        Route::post('/user-update/{id}', [UserController::class, 'update']);
        // Delete User
        Route::delete('/user-delete/{id}', [UserController::class, 'destroy']);




        // Dashboard Routes
        Route::get('/dashboard-data', [DashboardController::class, 'getDashboardData']);

        // -------------------- Warranty Routes --------------------
        // List Warranties
        Route::get('/warranty', [WarrantyController::class, 'warranty'])->name('warranty');
        // Edit Warranty
        Route::get('/warranty-edit/{id}', [WarrantyController::class, 'edit']);
        // Get All Warranties
        Route::get('/warranty-get-all', [WarrantyController::class, 'index']);
        // Store New Warranty
        Route::post('/warranty-store', [WarrantyController::class, 'store'])->name('warranty-store');
        // Update Warranty
        Route::post('/warranty-update/{id}', [WarrantyController::class, 'update']);
        // Delete Warranty
        Route::delete('/warranty-delete/{id}', [WarrantyController::class, 'destroy']);

        // -------------------- Guard Routes --------------------
        // Get all details using guard for the logged-in user
        Route::get('/get-all-details-using-guard', [AuthenticationController::class, 'getDetailsFromGuardDetailsUsingLoginUer']);
        // Update user location
        Route::get('/update-location', [AuthenticationController::class, 'updateLocation']);
        // Get all user location details
        Route::get('/user-location-get-all', [AuthenticationController::class, 'getAlluserDetails']);

        // -------------------- Profile Routes --------------------
        // Edit Profile
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        // Update Profile
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        // Delete Profile
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

        // -------------------- Brand Routes --------------------
        // List Brands
        Route::get('/brand', [BrandController::class, 'brand'])->name('brand');
        // Edit Brand
        Route::get('/brand-edit/{id}', [BrandController::class, 'edit']);
        // Get All Brands
        Route::get('/brand-get-all', [BrandController::class, 'index']);
        // Store New Brand
        Route::post('/brand-store', [BrandController::class, 'store']);
        // Update Brand
        Route::post('/brand-update/{id}', [BrandController::class, 'update']);
        // Delete Brand
        Route::delete('/brand-delete/{id}', [BrandController::class, 'destroy']);

        // -------------------- Unit Routes --------------------
        // List Units
        Route::get('/unit', [UnitController::class, 'unit'])->name('unit');
        // Edit Unit
        Route::get('/unit-edit/{id}', [UnitController::class, 'edit']);
        // Get All Units
        Route::get('/unit-get-all', [UnitController::class, 'index']);
        // Store New Unit
        Route::post('/unit-store', [UnitController::class, 'store']);
        // Update Unit
        Route::post('/unit-update/{id}', [UnitController::class, 'update']);
        // Delete Unit
        Route::delete('/unit-delete/{id}', [UnitController::class, 'destroy']);


        // -------------------- Dropdown Routes --------------------
        // Brand Dropdown
        Route::get('/get-brand', [BrandController::class, 'brandDropdown']);
        // Unit Dropdown
        Route::get('/get-unit', [UnitController::class, 'unitDropdown']);


        // -------------------- Product Routes --------------------
        // List, Add, Edit, Delete Products
        Route::get('/list-product', [ProductController::class, 'product'])->name('list-product');
        Route::get('/add-product', [ProductController::class, 'addProduct'])->name('add-product');
        Route::get('/edit-product/{id}', [ProductController::class, 'EditProduct'])->name('edit-product');
        Route::delete('/delete-product/{id}', [ProductController::class, 'destroy']);

        // Product Details & Stock History
        Route::get('/initial-product-details', [ProductController::class, 'initialProductDetails'])->name('product-details');
        Route::get('/product-get-details/{id}', [ProductController::class, 'getProductDetails']);
        Route::get('/products/stock-history/{id}', [ProductController::class, 'getStockHistory'])->name('productStockHistory');
        Route::get('/products/stocks', [ProductController::class, 'getAllProductStocks']);

        // Product Store/Update
        Route::post('/product/store', [ProductController::class, 'storeOrUpdate']);
        Route::post('/product/update/{id}', [ProductController::class, 'storeOrUpdate']);

        // Product Category & Subcategory
        Route::get('/product-get-by-category/{categoryId}', [ProductController::class, 'getProductsByCategory']);
        Route::get('/sub_category-details-get-by-main-category-id/{main_category_id}', [ProductController::class, 'showSubCategoryDetailsUsingByMainCategoryId'])->name('sub_category-details-get-by-main-category-id');

        // Product Price Update
        Route::get('/update-price', [ProductController::class, 'updatePrice'])->name('update-price');

        // Product Import/Export (Excel)
        Route::get('/import-product', [ProductController::class, 'importProduct'])->name('import-product');
        Route::post('/import-product-excel-store', [ProductController::class, 'importProductStore'])->name('import-product-excel-store');
        Route::get('/excel-product-blank-template-export', [ProductController::class, 'exportBlankTemplate'])->name('excel-product-blank-template-export');
        Route::get('/products/export-template', [ProductController::class, 'exportProducts'])->name('products.export-template');

        // Opening Stock
        Route::get('/opening-stock/{productId}', [ProductController::class, 'showOpeningStock'])->name('opening.stock');
        Route::get('/edit-opening-stock/{productId}', [ProductController::class, 'editOpeningStock'])->name('product.editOpeningStock');
        Route::post('/opening-stock/{productId}', [ProductController::class, 'storeOrUpdateOpeningStock']);
        Route::get('/opening-stocks-get-all', [ProductController::class, 'OpeningStockGetAll']);
        Route::get('/get-last-product', [ProductController::class, 'getLastProduct']);

        // Product Notifications
        Route::get('/notifications', [ProductController::class, 'getNotifications']);
        Route::post('/notifications/seen', [ProductController::class, 'markNotificationsAsSeen']);

        // IMEI Number Management
        Route::post('/save-or-update-imei', [ProductController::class, 'saveOrUpdateImei']);
        Route::post('/update-imei', [ProductController::class, 'updateSingleImei']);
        Route::post('/delete-imei', [ProductController::class, 'deleteImei']);
        Route::get('/get-imeis/{productId}', [ProductController::class, 'getImeis'])->name('getImeis');


        // -------------------- Role Routes --------------------
        // List Roles
        Route::get('/role', [RoleController::class, 'role'])->name('role');
        // Edit Role
        Route::get('/role-edit/{id}', [RoleController::class, 'edit']);
        // Get All Roles
        Route::get('/role-get-all', [RoleController::class, 'index']);
        // Store New Role
        Route::post('/role-store', [RoleController::class, 'store'])->name('role-store');
        // Update Role
        Route::post('/role-update/{id}', [RoleController::class, 'update']);
        // Delete Role
        Route::delete('/role-delete/{id}', [RoleController::class, 'destroy']);
        // Role Dropdown for User Select Box
        Route::get('/user-select-box-dropdown', [RoleController::class, 'SelectRoleNameDropdown'])->name('role.dropdown');

        // -------------------- Role and Permission Group Routes --------------------
        // View Group Role and Permission
        Route::get('/group-role-and-permission-view', [RoleAndPermissionController::class, 'groupRoleAndPermissionView'])->name('group-role-and-permission-view');
        // List Group Role and Permission
        Route::get('/group-role-and-permission', [RoleAndPermissionController::class, 'groupRoleAndPermission'])->name('group-role-and-permission');
        // Edit Role and Permission
        Route::get('/role-and-permission-edit/{role_id}', [RoleAndPermissionController::class, 'edit']);
        // Store Role and Permission
        Route::post('/role-and-permission-store', [RoleAndPermissionController::class, 'store'])->name('role-and-permission-store');
        // Update Role and Permission
        Route::post('/role-and-permission-update/{role_id}', [RoleAndPermissionController::class, 'update'])->name('group-and-permission-update');
        // Get All Role and Permission Groups
        Route::get('/role-and-permission-all', [RoleAndPermissionController::class, 'groupRoleAndPermissionList'])->name('role-and-permission-all');
        // Delete Role and Permission Group
        Route::delete('/role-and-permission-delete/{role_id}', [RoleAndPermissionController::class, 'destroy']);



        // -------------------- Sales Commission Agents Routes --------------------
        Route::get('/sales-commission-agent', [SalesCommissionAgentsController::class, 'SalesCommissionAgents'])->name('sales-commission-agent');
        Route::get('/sales-commission-agent-edit/{id}', [SalesCommissionAgentsController::class, 'edit']);
        Route::get('/sales-commission-agent-get-all', [SalesCommissionAgentsController::class, 'index']);
        Route::post('/sales-commission-agent-store', [SalesCommissionAgentsController::class, 'store'])->name('sales-commission-agent-store');
        Route::post('/sales-commission-agent-update/{id}', [SalesCommissionAgentsController::class, 'update']);
        Route::delete('/sales-commission-agent-delete/{id}', [SalesCommissionAgentsController::class, 'destroy']);

        // -------------------- Print Label Routes --------------------
        Route::get('/print-label', [PrintLabelController::class, 'printLabel'])->name('print-label');

        // -------------------- Variation Routes --------------------
        Route::get('/variation', [VariationController::class, 'variation'])->name('variation');
        Route::get('/variation-edit/{id}', [VariationController::class, 'edit']);
        Route::get('/variation-get-all', [VariationController::class, 'index']);
        Route::post('/variation-store', [VariationController::class, 'store'])->name('variation-title-store');
        Route::post('/variation-update/{id}', [VariationController::class, 'update']);
        Route::delete('/variation-delete/{id}', [VariationController::class, 'destroy']);

        // -------------------- Selling Price Group Routes --------------------
        Route::get('/selling-price-group', [SellingPriceGroupController::class, 'sellingPrice'])->name('selling-price-group');
        Route::get('/selling-price-group-edit/{id}', [SellingPriceGroupController::class, 'edit']);
        Route::get('/selling-price-group-get-all', [SellingPriceGroupController::class, 'index']);
        Route::post('/selling-price-group-store', [SellingPriceGroupController::class, 'store'])->name('selling-price-group-store');
        Route::post('/selling-price-group-update/{id}', [SellingPriceGroupController::class, 'update']);
        Route::delete('/selling-price-group-delete/{id}', [SellingPriceGroupController::class, 'destroy']);

        // -------------------- Main Category Routes --------------------
        Route::get('/main-category', [MainCategoryController::class, 'mainCategory'])->name('main-category');
        Route::get('/main-category-edit/{id}', [MainCategoryController::class, 'edit']);
        Route::get('/main-category-get-all', [MainCategoryController::class, 'index']);
        Route::post('/main-category-store', [MainCategoryController::class, 'store'])->name('main-category-store');
        Route::post('/main-category-update/{id}', [MainCategoryController::class, 'update']);
        Route::delete('/main-category-delete/{id}', [MainCategoryController::class, 'destroy']);

        // -------------------- Sub Category Routes --------------------
        Route::get('/sub-category', [SubCategoryController::class, 'SubCategory'])->name('sub-category');
        Route::get('/sub-category-edit/{id}', [SubCategoryController::class, 'edit']);
        Route::get('/sub-category-get-all', [SubCategoryController::class, 'index']);
        Route::post('/sub-category-store', [SubCategoryController::class, 'store'])->name('sub-category-store');
        Route::post('/sub-category-update/{id}', [SubCategoryController::class, 'update']);
        Route::delete('/sub-category-delete/{id}', [SubCategoryController::class, 'destroy']);

        // -------------------- Supplier Routes --------------------
        Route::get('/supplier', [SupplierController::class, 'supplier'])->name('supplier');
        Route::get('/supplier-edit/{id}', [SupplierController::class, 'edit']);
        Route::get('/supplier-get-all', [SupplierController::class, 'index']);
        Route::post('/supplier-store', [SupplierController::class, 'store']);
        Route::post('/supplier-update/{id}', [SupplierController::class, 'update']);
        Route::delete('/supplier-delete/{id}', [SupplierController::class, 'destroy']);

        // -------------------- Customer Routes --------------------
        Route::get('/customer', [CustomerController::class, 'customer'])->name('customer');
        Route::get('/customer-edit/{id}', [CustomerController::class, 'edit']);
        Route::get('/customer-get-all', [CustomerController::class, 'index']);
        Route::post('/customer-store', [CustomerController::class, 'store']);
        Route::post('/customer-update/{id}', [CustomerController::class, 'update']);
        Route::delete('/customer-delete/{id}', [CustomerController::class, 'destroy']);
        Route::get('/customer-get-by-id/{id}', [CustomerController::class, 'show']);

        // -------------------- Customer Group Routes --------------------
        Route::get('/customer-group', [CustomerGroupController::class, 'customerGroup'])->name('customer-group');
        Route::get('/customer-group-edit/{id}', [CustomerGroupController::class, 'edit']);
        Route::get('/customer-group-get-all', [CustomerGroupController::class, 'index']);
        Route::post('/customer-group-store', [CustomerGroupController::class, 'store'])->name('customer-group-store');
        Route::post('/customer-group-update/{id}', [CustomerGroupController::class, 'update']);
        Route::delete('/customer-group-delete/{id}', [CustomerGroupController::class, 'destroy']);

        // Import Contacts Routes
        Route::get('/import-contact', [ContactController::class, 'importContact'])->name('import-contact');

        // -------------------- Purchase Routes --------------------
        // List Purchases
        Route::get('/list-purchase', [PurchaseController::class, 'listPurchase'])->name('list-purchase');
        // Add Purchase
        Route::get('/add-purchase', [PurchaseController::class, 'addPurchase'])->name('add-purchase');
        // Store New Purchase
        Route::post('/purchases/store', [PurchaseController::class, 'storeOrUpdate']);
        // Update Purchase
        Route::post('/purchases/update/{id}', [PurchaseController::class, 'storeOrUpdate']);
        // Get All Purchases
        Route::get('/get-all-purchases', [PurchaseController::class, 'getAllPurchase']);
        // Get Purchase by ID
        Route::get('/get-purchase/{id}', [PurchaseController::class, 'getPurchase']);
        // Get All Purchases Product by ID
        Route::get('/get-all-purchases-product/{id}', [PurchaseController::class, 'getAllPurchasesProduct']);
        // Edit Purchase
        Route::get('purchase/edit/{id}', [PurchaseController::class, 'editPurchase']);
        // Get Purchase Products by Supplier
        Route::get('/purchase-products-by-supplier/{supplierId}', [PurchaseController::class, 'getPurchaseProductsBySupplier']);

        // -------------------- Purchase Return Routes --------------------
        // List Purchase Returns
        Route::get('/purchase-return', [PurchaseReturnController::class, 'purchaseReturn'])->name('purchase-return');
        // Add Purchase Return
        Route::get('/add-purchase-return', [PurchaseReturnController::class, 'addPurchaseReturn'])->name('add-purchase-return');
        // Store Purchase Return
        Route::post('/purchase-return/store', [PurchaseReturnController::class, 'storeOrUpdate']);
        // Get All Purchase Returns
        Route::get('/purchase-returns/get-All', [PurchaseReturnController::class, 'getAllPurchaseReturns']);
        // Get Purchase Return Details by ID
        Route::get('/purchase-returns/get-Details/{id}', [PurchaseReturnController::class, 'getPurchaseReturns']);
        // Edit Purchase Return
        Route::get('/purchase-return/edit/{id}', [PurchaseReturnController::class, 'edit']);
        // Get Product Details for Purchase Return by Supplier ID
        Route::get('/purchase-returns/get-product-details/{supplierId}', [PurchaseReturnController::class, 'getProductDetails']);
        // Update Purchase Return
        Route::post('/purchase-return/update/{id}', [PurchaseReturnController::class, 'storeOrUpdate']);

        // Sale Return Routes
        // Route::post('/sales-returns/store', [SaleReturnController::class, 'store']);
        // Route::get('/sales-returns', [SaleReturnController::class, 'addSaleReturn'])->name('sales-returns');

        // -------------------- Sale Return Routes --------------------
        // Get all sale returns
        Route::get('/sale-returns', [SaleReturnController::class, 'getAllSaleReturns']);
        // Get sale return by ID
        Route::get('/sale-return-get/{id}', [SaleReturnController::class, 'getSaleReturnById']);
        // Add sale return page
        Route::get('/sale-return/add', [SaleReturnController::class, 'addSaleReturn'])->name('sale-return/add');
        // List sale returns page
        Route::get('/sale-return/list', [SaleReturnController::class, 'listSaleReturn'])->name('sale-return/list');
        // Store new or update sale return
        Route::post('/sale-return/store', [SaleReturnController::class, 'storeOrUpdate']);
        // Update sale return by ID
        Route::put('/sale-return/update/{id}', [SaleReturnController::class, 'storeOrUpdate']);
        // Edit sale return page
        Route::get('/sale-return/edit/{id}', [SaleReturnController::class, 'editSaleReturn']);
        // Print sale return receipt
        Route::get('/sale-return/print/{id}', [SaleReturnController::class, 'printReturnReceipt'])->name('sale.return.print');

        // -------------------- Stock Transfer Routes --------------------
        // List all stock transfers
        Route::get('/stock-transfers', [StockTransferController::class, 'index']);
        // Stock transfer list page
        Route::get('/list-stock-transfer', [StockTransferController::class, 'stockTransfer'])->name('list-stock-transfer');
        // Add stock transfer page
        Route::get('/add-stock-transfer', [StockTransferController::class, 'addStockTransfer'])->name('add-stock-transfer');
        // Store new stock transfer
        Route::post('/stock-transfer/store', [StockTransferController::class, 'storeOrUpdate']);
        // Update stock transfer by ID
        Route::put('/stock-transfer/update/{id}', [StockTransferController::class, 'storeOrUpdate']);
        // Edit stock transfer page
        Route::get('/edit-stock-transfer/{id}', [StockTransferController::class, 'edit']);

        // -------------------- Sale Routes --------------------
        // List all sales
        Route::get('/list-sale', [SaleController::class, 'listSale'])->name('list-sale');
        // POS create page
        Route::get('/pos-create', [SaleController::class, 'pos'])->name('pos-create');
        // POS create new version page
        Route::get('/pos-create/new', [SaleController::class, 'pos2'])->name('pos-create/new');
        // POS sales list
        Route::get('/pos-list', [SaleController::class, 'posList'])->name('pos-list');
        // Draft sales list
        Route::get('/draft-list', [SaleController::class, 'draft'])->name('draft-list');
        // Quotation sales list
        Route::get('/quotation-list', [SaleController::class, 'quotation'])->name('quotation-list');
        // Store new sale
        Route::post('/sales/store', [SaleController::class, 'storeOrUpdate']);
        // Update sale
        Route::post('/sales/update/{id}', [SaleController::class, 'storeOrUpdate']);
        // Get all sales
        Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
        // Get sale details by ID
        Route::get('/sales_details/{id}', [SaleController::class, 'salesDetails']);
        // Edit sale page
        Route::get('/sales/edit/{id}', [SaleController::class, 'editSale'])->name('sales.edit');
        // Update sale by ID (PUT)
        Route::put('/sales/{id}', [SaleController::class, 'update'])->name('sales.update');
        // Delete sale by ID
        Route::delete('/sales/delete/{id}', [SaleController::class, 'destroy'])->name('sales.destroy');

        // -------------------- Sale Reports --------------------
        // Sales report view page
        Route::get('/sales-report', [SaleController::class, 'saleDailyReport'])->name('sales-report');
        // Fetch all daily report sales
        Route::get('/daily-sales-report', [SaleController::class, 'dailyReport']);

        // -------------------- Suspended Sales --------------------
        // Fetch all suspended sales
        Route::get('/sales/suspended', [SaleController::class, 'fetchSuspendedSales']);
        // Resume a suspended sale
        Route::get('/pos/sales/edit/{id}', [SaleController::class, 'show']);
        // Delete a suspended sale
        Route::delete('/sales/delete-suspended/{id}', [SaleController::class, 'deleteSuspendedSale']);

        // -------------------- Print Sales --------------------
        // Print recent transaction
        Route::get('/sales/print-recent-transaction/{id}', [SaleController::class, 'printRecentTransaction']);

        // Expense Parent Category Routes
        Route::get('/expense-parent-catergory', [ExpenseParentCategoryController::class, 'mainCategory'])->name('expense-parent-catergory');
        Route::get('/expense-parent-catergory-edit/{id}', [ExpenseParentCategoryController::class, 'edit']);
        Route::get('/expense-parent-catergory-get-all', [ExpenseParentCategoryController::class, 'index']);
        Route::post('/expense-parent-catergory-store', [ExpenseParentCategoryController::class, 'store'])->name('expense-parent-catergory-store');
        Route::post('/expense-parent-catergory-update/{id}', [ExpenseParentCategoryController::class, 'update']);
        Route::delete('/expense-parent-catergory-delete/{id}', [ExpenseParentCategoryController::class, 'destroy']);

        // Expense Sub Category Routes
        Route::get('/sub-expense-category', [ExpenseSubCategoryController::class, 'SubCategory'])->name('sub-expense-category');
        Route::get('/sub-expense-category-edit/{id}', [ExpenseSubCategoryController::class, 'edit']);
        Route::get('/sub-expense-category-get-all', [ExpenseSubCategoryController::class, 'index']);
        Route::post('/sub-expense-category-store', [ExpenseSubCategoryController::class, 'store'])->name('sub-expense-category-store');
        Route::post('/sub-expense-category-update/{id}', [ExpenseSubCategoryController::class, 'update']);
        Route::delete('/sub-expense-category-delete/{id}', [ExpenseSubCategoryController::class, 'destroy']);

        // Variation Title Routes
        Route::get('/variation-title', [VariationTitleController::class, 'variationTitle'])->name('variation-title');
        Route::get('/variation-title-edit/{id}', [VariationTitleController::class, 'edit']);
        Route::get('/variation-title-get-all', [VariationTitleController::class, 'index']);
        Route::post('/variation-title-store', [VariationTitleController::class, 'store'])->name('variation-title-store');
        Route::post('/variation-title-update/{id}', [VariationTitleController::class, 'update']);
        Route::delete('/variation-title-delete/{id}', [VariationTitleController::class, 'destroy']);

        // Location Routes
        Route::get('/location', [LocationController::class, 'location'])->name('location');
        Route::get('/location-edit/{id}', [LocationController::class, 'edit']);
        Route::get('/location-get-all', [LocationController::class, 'index']);
        Route::post('/location-store', [LocationController::class, 'store']);
        Route::post('/location-update/{id}', [LocationController::class, 'update']);
        Route::delete('/location-delete/{id}', [LocationController::class, 'destroy']);

        // Import Opening Stock Routes
        Route::get('/import-opening-stock', [OpeningStockController::class, 'importOpeningStock'])->name('import-opening-stock');
        Route::get('/import-opening-stock-edit/{id}', [OpeningStockController::class, 'edit']);
        Route::get('/import-opening-stock-get-all', [OpeningStockController::class, 'index']);
        Route::post('/import-opening-stock-store', [OpeningStockController::class, 'store']);
        Route::post('/import-opening-stock-update/{id}', [OpeningStockController::class, 'update']);
        Route::delete('/import-opening-stock-delete/{id}', [OpeningStockController::class, 'destroy']);

        // Excel Import/Export Routes
        Route::get('/excel-export-student', [OpeningStockController::class, 'export'])->name('excel-export-student');
        Route::get('/excel-blank-template-export', [OpeningStockController::class, 'exportBlankTemplate'])->name('excel-blank-template-export');
        Route::post('/import-opening-stck-excel-store', [OpeningStockController::class, 'importOpeningStockStore']);
        Route::post('/opening-stock-store', [OpeningStockController::class, 'store'])->name('opening-stock.store');

        // Stock Adjustment Routes
        Route::get('/add-stock-adjustment', [StockAdjustmentController::class, 'addStockAdjustment'])->name('add-stock-adjustment');
        Route::get('/list-stock-adjustment', [StockAdjustmentController::class, 'stockAdjustmentList'])->name('list-stock-adjustment');
        Route::get('/stock-adjustments', [StockAdjustmentController::class, 'index'])->name('stock-adjustments.index');
        Route::get('/edit-stock-adjustment/{id}', [StockAdjustmentController::class, 'edit'])->name('stock-adjustments.edit');
        Route::get('/stock-adjustments/{id}', [StockAdjustmentController::class, 'show'])->name('stock-adjustments.show');
        Route::post('/stock-adjustment/store', [StockAdjustmentController::class, 'storeOrUpdate']);
        Route::put('/stock-adjustment/update/{id}', [StockAdjustmentController::class, 'storeOrUpdate']);

        Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
        Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
        Route::delete('/cart/remove/{rowId}', [CartController::class, 'remove'])->name('cart.remove');
        Route::put('/cart/update/{rowId}', [CartController::class, 'update'])->name('cart.update');

        Route::get('payments', [PaymentController::class, 'index']);
        Route::post('payments', [PaymentController::class, 'storeOrUpdate']);
        Route::get('payments/{payment}', [PaymentController::class, 'show']);
        Route::put('payments/{payment}', [PaymentController::class, 'storeOrUpdate']);
        Route::delete('payments/{payment}', [PaymentController::class, 'destroy']);

        // web.php (Routes)
        Route::post('/submit-bulk-payment', [PaymentController::class, 'submitBulkPayment']);

        //bulk payments pages
        Route::get('/add-sale-bulk-payments', [PaymentController::class, 'addSaleBulkPayments'])->name('add-sale-bulk-payments');
        Route::get('/add-purchase-bulk-payments', [PaymentController::class, 'addPurchaseBulkPayments'])->name('add-purchase-bulk-payments');


        Route::post('/save-changes', [ProductController::class, 'saveChanges']);
        Route::post('/apply-discount', [ProductController::class, 'applyDiscount'])->name('products.applyDiscount');


        // Discount Routes
        Route::get('/discounts', [DiscountController::class, 'index'])->name('discounts.index');
        Route::get('/discounts/data', [DiscountController::class, 'getDiscountsData'])->name('discounts.data');
        Route::post('/discounts', [DiscountController::class, 'store'])->name('discounts.store');
        Route::get('/discounts/{discount}/edit', [DiscountController::class, 'edit'])->name('discounts.edit');
        Route::put('/discounts/{discount}', [DiscountController::class, 'update'])->name('discounts.update');
        Route::delete('/discounts/{discount}', [DiscountController::class, 'destroy'])->name('discounts.destroy');
        Route::post('/discounts/{discount}/toggle-status', [DiscountController::class, 'toggleStatus'])->name('discounts.toggle-status');
        Route::get('/discounts/export', [DiscountController::class, 'export'])->name('discounts.export');
        Route::get('/discounts/{discount}/products', [DiscountController::class, 'getProducts'])->name('discounts.products');


        //report routes
        Route::get('/stock-report', [ReportController::class, 'stockHistory'])->name('stock.report');
        Route::get('/activity-log', [ReportController::class, 'activityLogPage'])->name('activity-log.activityLogPage');
        Route::post('/activity-log/fetch', [ReportController::class, 'fetchActivityLog'])->name('activity-log.fetch');
    });
});
