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
    ProfileController,
    LocationController,
    PurchaseController,
    SupplierController,
    WarrantyController,
    DashboardController,
    PrintLabelController,
    SubCategoryController,
    MainCategoryController,
    OpeningStockController,
    CustomerGroupController,
    StockTransferController,
    AuthenticationController,
    PurchaseReturnController,
    VariationController,
    VariationTitleController,
    SellingPriceGroupController,
    ExpenseSubCategoryController,
    ExpenseParentCategoryController,
    ExpenseController,
    SaleReturnController,
    SalesCommissionAgentsController,
    RoleController,
    RoleAndPermissionController,
    StockAdjustmentController,
    CartController,
    PaymentController,
    DiscountController,
    ReportController,
    SettingController
};
use App\Http\Controllers\Web\{
    SalesRepController,
    RouteController,
    RouteCityController,
    CityController,
    SalesRepTargetController,
    ProductController,
    CustomerController
};





// Helper function
if (!function_exists('set_active')) {
    function set_active($route)
    {
        if (is_array($route)) {
            return in_array(Request::path(), $route) ? 'active' : '';
        }
        return Request::path() == $route ? 'active' : '';
    }
}

// -------------------- Dashboard Route --------------------
Route::get('/dashboard', function () {
    return view('includes.dashboards.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// -------------------- Auth Routes --------------------
require __DIR__ . '/auth.php';

// -------------------- Protected Routes (Auth + Session) --------------------
Route::middleware(['auth'])->group(function () {

    // -------------------- Dynamic Role Middleware --------------------
    Route::group(['middleware' => function ($request, $next) {
        $role = Auth::user()->role_name ?? null;
        if ($role) {
            $request->route()->middleware("role:$role");
        }
        return $next($request);
    }], function () {


         // -------------------- DashboardController Routes --------------------
        Route::get('/dashboard-data', [DashboardController::class, 'getDashboardData']);
        // -------------------- UserController Routes --------------------
        // User Management
        Route::get('/user', [UserController::class, 'user'])->name('user');
        Route::get('/user-edit/{id}', [UserController::class, 'edit']);
        Route::get('/user-get-all', [UserController::class, 'index']);
        Route::post('/user-store', [UserController::class, 'store']);
        Route::post('/user-update/{id}', [UserController::class, 'update']);
        Route::delete('/user-delete/{id}', [UserController::class, 'destroy']);

         // -------------------- RoleController Routes --------------------
        Route::get('/role', [RoleController::class, 'role'])->name('role');
        Route::get('/role-get-all', [RoleController::class, 'index']);
        Route::get('/role-edit/{id}', [RoleController::class, 'edit']);
        Route::post('/role-store', [RoleController::class, 'store'])->name('role-store');
        Route::post('/role-update/{id}', [RoleController::class, 'update']);
        Route::delete('/role-delete/{id}', [RoleController::class, 'destroy']);
        Route::get('/user-select-box-dropdown', [RoleController::class, 'SelectRoleNameDropdown'])->name('role.dropdown');

        // -------------------- RoleAndPermissionController Routes --------------------
        Route::get('/group-role-and-permission-view', [RoleAndPermissionController::class, 'groupRoleAndPermissionView'])->name('group-role-and-permission-view');
        Route::get('/group-role-and-permission', [RoleAndPermissionController::class, 'groupRoleAndPermission'])->name('group-role-and-permission');
        Route::get('/role-and-permission-edit/{role_id}', [RoleAndPermissionController::class, 'edit']);
        Route::post('/role-and-permission-store', [RoleAndPermissionController::class, 'store'])->name('role-and-permission-store');
        Route::post('/role-and-permission-update/{role_id}', [RoleAndPermissionController::class, 'update'])->name('group-and-permission-update');
        Route::get('/role-and-permission-all', [RoleAndPermissionController::class, 'groupRoleAndPermissionList'])->name('role-and-permission-all');
        Route::get('/get-role-permissions/{role_id}', [RoleAndPermissionController::class, 'getRolePermissions'])->name('get-role-permissions');
        Route::delete('/role-and-permission-delete/{role_id}', [RoleAndPermissionController::class, 'destroy']);

        // -------------------- SupplierController Routes --------------------
        Route::get('/supplier', [SupplierController::class, 'supplier'])->name('supplier');
        Route::get('/supplier-edit/{id}', [SupplierController::class, 'edit']);
        Route::get('/supplier-get-all', [SupplierController::class, 'index']);
        Route::post('/supplier-store', [SupplierController::class, 'store']);
        Route::post('/supplier-update/{id}', [SupplierController::class, 'update']);
        Route::delete('/supplier-delete/{id}', [SupplierController::class, 'destroy']);

        // -------------------- CustomerController Routes --------------------
        Route::get('/customer', [CustomerController::class, 'customer'])->name('customer');
        Route::get('/customer-edit/{id}', [CustomerController::class, 'edit']);
        Route::get('/customer-get-all', [CustomerController::class, 'index']);
        Route::get('/customer-get-by-route/{routeId}', [CustomerController::class, 'getCustomersByRoute']);
        Route::post('/customers/filter-by-cities', [CustomerController::class, 'filterByCities']);
        Route::post('/customer-store', [CustomerController::class, 'store']);
        Route::post('/customer-update/{id}', [CustomerController::class, 'update']);
        Route::delete('/customer-delete/{id}', [CustomerController::class, 'destroy']);
        Route::get('/customer-get-by-id/{id}', [CustomerController::class, 'show']);


         // Customer Ledger Routes (Redirected to Account Ledger)
        Route::get('/customer-ledger', function() {
            $customerId = request('customer_id');
            $url = route('account.ledger');
            if ($customerId) {
                $url .= '?customer_id=' . $customerId;
            }
            return redirect($url);
        })->name('customer.ledger');
        Route::get('/customer-ledger-data', [PaymentController::class, 'getCustomerLedger'])->name('customer.ledger.data');
        Route::post('/apply-customer-advance', [PaymentController::class, 'applyCustomerAdvance'])->name('customer.apply.advance');

        // Supplier Ledger Routes (Redirected to Account Ledger)
        Route::get('/supplier-ledger', function() {
            $supplierId = request('supplier_id');
            $url = route('account.ledger');
            if ($supplierId) {
                $url .= '?supplier_id=' . $supplierId;
            }
            return redirect($url);
        })->name('supplier.ledger');
        Route::get('/supplier-ledger-data', [PaymentController::class, 'getSupplierLedger'])->name('supplier.getSupplierLedgerData');
        Route::post('/apply-supplier-advance', [PaymentController::class, 'applySupplierAdvance'])->name('supplier.applySupplierAdvance');
        Route::get('/supplier-details', [PaymentController::class, 'getSupplierDetails'])->name('supplier.getSupplierDetails');
        Route::get('/suppliers-list', [PaymentController::class, 'getSuppliersData'])->name('contact.supplier.getSuppliers');
        Route::get('/business-locations', [PaymentController::class, 'getBusinessLocations'])->name('business.getBusinessLocation');

        
         // -------------------- ProductController Routes --------------------
        // Product CRUD
        Route::get('/list-product', [ProductController::class, 'product'])->name('list-product');
        Route::get('/add-product', [ProductController::class, 'addProduct'])->name('add-product');
        Route::get('/edit-product/{id}', [ProductController::class, 'EditProduct'])->name('edit-product');
        Route::post('/toggle-product-status/{id}', [ProductController::class, 'toggleStatus']);
        // Product Details & Stock
        Route::get('/initial-product-details', [ProductController::class, 'initialProductDetails'])->name('product-details');
        Route::get('/product-get-details/{id}', [ProductController::class, 'getProductDetails']);
        Route::get('/products/stock-history/{id}', [ProductController::class, 'getStockHistory'])->name('productStockHistory');
        Route::get('/products/stocks', [ProductController::class, 'getAllProductStocks']);
        Route::get('/products/stocks/autocomplete', [ProductController::class, 'autocompleteStock']);
        
        // Diagnostic route for troubleshooting hosting issues
        Route::get('/diagnostic/system-check', [\App\Http\Controllers\Web\DiagnosticController::class, 'checkSystem']);
        
        // Product Store/Update
        Route::post('/product/store', [ProductController::class, 'storeOrUpdate']);
        Route::post('/product/update/{id}', [ProductController::class, 'storeOrUpdate']);
        Route::post('/product/check-sku', [ProductController::class, 'checkSkuUniqueness']); // Real-time SKU validation
        // Category/Subcategory
        Route::get('/product-get-by-category/{categoryId}', [ProductController::class, 'getProductsByCategory']);
        Route::get('/sub_category-details-get-by-main-category-id/{main_category_id}', [ProductController::class, 'showSubCategoryDetailsUsingByMainCategoryId'])->name('sub_category-details-get-by-main-category-id');
        // Price Update
        Route::get('/update-price', [ProductController::class, 'updatePrice'])->name('update-price');
        // Import/Export
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
        // Notifications
        Route::get('/notifications', [ProductController::class, 'getNotifications']);
        Route::post('/notifications/seen', [ProductController::class, 'markNotificationsAsSeen']);
        // IMEI Management
        Route::post('/save-or-update-imei', [ProductController::class, 'saveOrUpdateImei']);
        Route::post('/update-imei', [ProductController::class, 'updateSingleImei']);
        Route::post('/delete-imei', [ProductController::class, 'deleteImei']);
        Route::get('/get-imeis/{productId}', [ProductController::class, 'getImeis'])->name('getImeis');
        // Save Changes & Discount
        Route::post('/save-changes', [ProductController::class, 'saveChanges']);
        Route::post('/get-product-locations', [ProductController::class, 'getProductLocations']);
        Route::post('/apply-discount', [ProductController::class, 'applyDiscount'])->name('products.applyDiscount');
        // Batch Price Management
        Route::get('/product/{productId}/batches', [ProductController::class, 'getProductBatches'])->name('product.batches');
        Route::post('/batches/update-prices', [ProductController::class, 'updateBatchPrices'])->name('batches.updatePrices');

        // -------------------- DiscountController Routes --------------------
        Route::get('/discounts', [DiscountController::class, 'index'])->name('discounts.index');
        Route::get('/discounts/data', [DiscountController::class, 'getDiscountsData'])->name('discounts.data');
        Route::post('/discounts', [DiscountController::class, 'store'])->name('discounts.store');
        Route::get('/discounts/{discount}/edit', [DiscountController::class, 'edit'])->name('discounts.edit');
        Route::put('/discounts/{discount}', [DiscountController::class, 'update'])->name('discounts.update');
        Route::delete('/discounts/{discount}', [DiscountController::class, 'destroy'])->name('discounts.destroy');
        Route::post('/discounts/{discount}/toggle-status', [DiscountController::class, 'toggleStatus'])->name('discounts.toggle-status');
        Route::get('/discounts/export', [DiscountController::class, 'export'])->name('discounts.export');
        Route::get('/discounts/{discount}/products', [DiscountController::class, 'getProducts'])->name('discounts.products');

       
        // -------------------- UnitController Routes --------------------
        Route::get('/unit', [UnitController::class, 'unit'])->name('unit');
        Route::get('/unit-edit/{id}', [UnitController::class, 'edit']);
        Route::get('/unit-get-all', [UnitController::class, 'index']);
        Route::post('/unit-store', [UnitController::class, 'store']);
        Route::post('/unit-update/{id}', [UnitController::class, 'update']);
        Route::delete('/unit-delete/{id}', [UnitController::class, 'destroy']);
        Route::get('/get-unit', [UnitController::class, 'unitDropdown']);
        // -------------------- BrandController Routes --------------------
        Route::get('/brand', [BrandController::class, 'brand'])->name('brand');
        Route::get('/brand-edit/{id}', [BrandController::class, 'edit']);
        Route::get('/brand-get-all', [BrandController::class, 'index']);
        Route::post('/brand-store', [BrandController::class, 'store']);
        Route::post('/brand-update/{id}', [BrandController::class, 'update']);
        Route::delete('/brand-delete/{id}', [BrandController::class, 'destroy']);
        Route::get('/get-brand', [BrandController::class, 'brandDropdown']);

        // -------------------- MainCategoryController Routes --------------------
        Route::get('/main-category', [MainCategoryController::class, 'mainCategory'])->name('main-category');
        Route::get('/main-category-edit/{id}', [MainCategoryController::class, 'edit']);
        Route::get('/main-category-get-all', [MainCategoryController::class, 'index']);
        Route::post('/main-category-store', [MainCategoryController::class, 'store'])->name('main-category-store');
        Route::post('/main-category-update/{id}', [MainCategoryController::class, 'update']);
        Route::delete('/main-category-delete/{id}', [MainCategoryController::class, 'destroy']);

        // -------------------- SubCategoryController Routes --------------------
        Route::get('/sub-category', [SubCategoryController::class, 'SubCategory'])->name('sub-category');
        Route::get('/sub-category-edit/{id}', [SubCategoryController::class, 'edit']);
        Route::get('/sub-category-get-all', [SubCategoryController::class, 'index']);
        Route::post('/sub-category-store', [SubCategoryController::class, 'store'])->name('sub-category-store');
        Route::post('/sub-category-update/{id}', [SubCategoryController::class, 'update']);
        Route::delete('/sub-category-delete/{id}', [SubCategoryController::class, 'destroy']);

        // -------------------- WarrantyController Routes --------------------
        Route::get('/warranty', [WarrantyController::class, 'warranty'])->name('warranty');
        Route::get('/warranty-edit/{id}', [WarrantyController::class, 'edit']);
        Route::get('/warranty-get-all', [WarrantyController::class, 'index']);
        Route::post('/warranty-store', [WarrantyController::class, 'store'])->name('warranty-store');
        Route::post('/warranty-update/{id}', [WarrantyController::class, 'update']);
        Route::delete('/warranty-delete/{id}', [WarrantyController::class, 'destroy']);
      

         // -------------------- PurchaseController Routes --------------------
        Route::get('/list-purchase', [PurchaseController::class, 'listPurchase'])->name('list-purchase');
        Route::get('/add-purchase', [PurchaseController::class, 'addPurchase'])->name('add-purchase');
        Route::post('/purchases/store', [PurchaseController::class, 'storeOrUpdate']);
        Route::post('/purchases/update/{id}', [PurchaseController::class, 'storeOrUpdate']);
        Route::get('/get-all-purchases', [PurchaseController::class, 'getAllPurchase']);
        Route::get('/get-purchase/{id}', [PurchaseController::class, 'getPurchase']);
        Route::get('/get-all-purchases-product/{id}', [PurchaseController::class, 'getAllPurchasesProduct']);
        Route::get('purchase/edit/{id}', [PurchaseController::class, 'editPurchase']);
        Route::get('/purchase-products-by-supplier/{supplierId}', [PurchaseController::class, 'getPurchaseProductsBySupplier']);
        // Routes for fixing payment calculation issues
        Route::post('/purchases/recalculate-total/{id}', [PurchaseController::class, 'recalculatePurchaseTotal']);
        Route::post('/purchases/recalculate-all-totals', [PurchaseController::class, 'recalculateAllPurchaseTotals']);
        
        // IMEI Management Routes for Purchases
        Route::get('/purchases/{id}/imei-products', [PurchaseController::class, 'getPurchaseImeiProducts']);
        Route::post('/purchases/add-imei', [PurchaseController::class, 'addImeiToPurchaseProduct']);
        Route::post('/purchases/remove-imei', [PurchaseController::class, 'removeImeiFromPurchaseProduct']);
        Route::post('/purchases/update-imei', [PurchaseController::class, 'updateImeiForPurchaseProduct']);
        Route::post('/purchases/bulk-add-imei', [PurchaseController::class, 'bulkAddImeiToPurchaseProduct']);

        // -------------------- PurchaseReturnController Routes --------------------
        Route::get('/purchase-return', [PurchaseReturnController::class, 'purchaseReturn'])->name('purchase-return');
        Route::get('/add-purchase-return', [PurchaseReturnController::class, 'addPurchaseReturn'])->name('add-purchase-return');
        Route::post('/purchase-return/store', [PurchaseReturnController::class, 'storeOrUpdate']);
        Route::get('/purchase-returns/get-All', [PurchaseReturnController::class, 'getAllPurchaseReturns']);
        Route::get('/purchase-returns/get-Details/{id}', [PurchaseReturnController::class, 'getPurchaseReturns']);
        Route::get('/purchase-return/edit/{id}', [PurchaseReturnController::class, 'edit']);
        Route::get('/purchase-returns/get-product-details/{supplierId}', [PurchaseReturnController::class, 'getProductDetails']);
        Route::post('/purchase-return/update/{id}', [PurchaseReturnController::class, 'storeOrUpdate']);

        // -------------------- SaleController Routes --------------------
        Route::get('/list-sale', [SaleController::class, 'listSale'])->name('list-sale');
        Route::get('/pos-create', [SaleController::class, 'pos'])->name('pos-create');
        Route::get('/pos-list', [SaleController::class, 'posList'])->name('pos-list');
        Route::get('/draft-list', [SaleController::class, 'draft'])->name('draft-list');
        Route::get('/quotation-list', [SaleController::class, 'quotation'])->name('quotation-list');
        Route::get('/sale-orders-list', [SaleController::class, 'saleOrdersList'])->name('sale-orders-list');
        Route::post('/sale-orders/convert-to-invoice/{id}', [SaleController::class, 'convertToInvoice'])->name('sale-orders.convert');
        Route::post('/sale-orders/update/{id}', [SaleController::class, 'updateSaleOrder'])->name('sale-orders.update');
        Route::post('/sales/cancel-converted-invoice/{id}', [SaleController::class, 'cancelConvertedInvoice'])->name('sales.cancel-converted');
        Route::post('/sales/store', [SaleController::class, 'storeOrUpdate']);
        Route::post('/sales/update/{id}', [SaleController::class, 'storeOrUpdate']);
        Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
         Route::get('/sales/paginated', [SaleController::class, 'getDataTableSales'])->name('sales.paginated');
        Route::get('/sales_details/{id}', [SaleController::class, 'salesDetails']);
        // Suspended Sales - These need to come before the generic routes
        Route::get('/sales/suspended', [SaleController::class, 'fetchSuspendedSales']);
        Route::delete('/sales/delete-suspended/{id}', [SaleController::class, 'deleteSuspendedSale']);
        
        Route::get('/sales/edit/{id}', [SaleController::class, 'editSale'])->name('sales.edit');
        Route::delete('/sales/delete/{id}', [SaleController::class, 'destroy'])->name('sales.destroy');
        Route::get('/sales/{invoiceNo}', [SaleController::class, 'getSaleByInvoiceNo']);
        
        // Sales Reports - These need to come before the generic routes
        Route::get('/sales-report', [SaleController::class, 'saleDailyReport'])->name('sales-report');
        Route::get('/daily-sales-report', [SaleController::class, 'dailyReport']);
        
        // Print Sales - These need to come before the generic routes
        Route::get('/sales/print-recent-transaction/{id}', [SaleController::class, 'printRecentTransaction']);        
        Route::post('/pos/log-pricing-error', [SaleController::class, 'logPricingError']);

         // -------------------- SaleReturnController Routes --------------------
        Route::get('/sale-returns', [SaleReturnController::class, 'getAllSaleReturns']);
        Route::get('/sale-return-get/{id}', [SaleReturnController::class, 'getSaleReturnById']);
        Route::get('/sale-return/add', [SaleReturnController::class, 'addSaleReturn'])->name('sale-return/add');
        Route::get('/sale-return/list', [SaleReturnController::class, 'listSaleReturn'])->name('sale-return/list');
        Route::post('/sale-return/store', [SaleReturnController::class, 'storeOrUpdate']);
        Route::put('/sale-return/update/{id}', [SaleReturnController::class, 'storeOrUpdate']);
        Route::get('/sale-return/edit/{id}', [SaleReturnController::class, 'editSaleReturn']);
        Route::get('/sale-return/print/{id}', [SaleReturnController::class, 'printReturnReceipt'])->name('sale.return.print');

       
         // -------------------- Cheque Management Routes --------------------
        Route::get('/cheque-management', [SaleController::class, 'chequeManagement'])->name('cheque-management');
        Route::get('/cheque-guide', function() { return view('sell.cheque-guide'); })->name('cheque-guide');
        Route::post('/cheque/update-status/{paymentId}', [SaleController::class, 'updateChequeStatus'])->name('cheque.update-status');
        Route::get('/cheque/status-history/{paymentId}', [SaleController::class, 'chequeStatusHistory'])->name('cheque.status-history');
        Route::get('/cheque/pending-reminders', [SaleController::class, 'pendingChequeReminders'])->name('cheque.pending-reminders');
        Route::post('/cheque/mark-reminder-sent/{reminderId}', [SaleController::class, 'markReminderSent'])->name('cheque.mark-reminder-sent');
        Route::post('/cheque/bulk-update-status', [SaleController::class, 'bulkUpdateChequeStatus'])->name('cheque.bulk-update-status');

         // -------------------- PaymentController Routes --------------------
        Route::get('payments', [PaymentController::class, 'index']);
        Route::post('payments', [PaymentController::class, 'storeOrUpdate']);
        Route::get('payments/{payment}', [PaymentController::class, 'show']);
        Route::put('payments/{payment}', [PaymentController::class, 'storeOrUpdate']);
        Route::delete('payments/{payment}', [PaymentController::class, 'destroy']);
        Route::post('/submit-bulk-payment', [PaymentController::class, 'submitBulkPayment']);
        Route::get('/add-sale-bulk-payments', [PaymentController::class, 'addSaleBulkPayments'])->name('add-sale-bulk-payments');
        Route::get('/add-purchase-bulk-payments', [PaymentController::class, 'addPurchaseBulkPayments'])->name('add-purchase-bulk-payments');
        Route::get('/manage-bulk-payments', function() { return view('bulk_payments.bulk_payments_list'); })->name('manage-bulk-payments');        
        // Bulk Payment Management Routes
        Route::get('/bulk-payments-list', [PaymentController::class, 'getBulkPaymentsList'])->name('bulk.payments.list');
        Route::get('/bulk-payment/{id}/edit', [PaymentController::class, 'editBulkPayment'])->name('bulk.payment.edit');
        Route::put('/bulk-payment/{id}', [PaymentController::class, 'updateBulkPayment'])->name('bulk.payment.update');
        Route::delete('/bulk-payment/{id}', [PaymentController::class, 'deleteBulkPayment'])->name('bulk.payment.delete');
        Route::get('/bulk-payment-logs', [PaymentController::class, 'getBulkPaymentLogs'])->name('bulk.payment.logs');
        
       
        // -------------------- StockTransferController Routes --------------------
        Route::get('/stock-transfers', [StockTransferController::class, 'index']);
        Route::get('/list-stock-transfer', [StockTransferController::class, 'stockTransfer'])->name('list-stock-transfer');
        Route::get('/add-stock-transfer', [StockTransferController::class, 'addStockTransfer'])->name('add-stock-transfer');
        Route::post('/stock-transfer/store', [StockTransferController::class, 'storeOrUpdate']);
        Route::put('/stock-transfer/update/{id}', [StockTransferController::class, 'storeOrUpdate']);
        Route::get('/edit-stock-transfer/{id}', [StockTransferController::class, 'edit']);
        Route::delete('/stock-transfer/delete/{id}', [StockTransferController::class, 'destroy']);
        Route::get('/stock-transfer/get/{id}', [StockTransferController::class, 'getStockTransferWithActivityLog']);

        // -------------------- StockAdjustmentController Routes --------------------
        Route::get('/add-stock-adjustment', [StockAdjustmentController::class, 'addStockAdjustment'])->name('add-stock-adjustment');
        Route::get('/list-stock-adjustment', [StockAdjustmentController::class, 'stockAdjustmentList'])->name('list-stock-adjustment');
        Route::get('/stock-adjustments', [StockAdjustmentController::class, 'index'])->name('stock-adjustments.index');
        Route::get('/edit-stock-adjustment/{id}', [StockAdjustmentController::class, 'edit'])->name('stock-adjustments.edit');
        Route::get('/stock-adjustments/{id}', [StockAdjustmentController::class, 'show'])->name('stock-adjustments.show');
        Route::post('/stock-adjustment/store', [StockAdjustmentController::class, 'storeOrUpdate']);
        Route::put('/stock-adjustment/update/{id}', [StockAdjustmentController::class, 'storeOrUpdate']);


        // -------------------- ExpenseController Routes --------------------
        Route::get('/expense-list', [ExpenseController::class, 'expenseList'])->name('expense.list');
        Route::get('/expense-create', [ExpenseController::class, 'create'])->name('expense.create');
        Route::get('/expense-edit/{id}', [ExpenseController::class, 'edit'])->name('expense.edit');
        Route::get('/expense-show/{id}', [ExpenseController::class, 'show']);
        Route::get('/expense-get-all', [ExpenseController::class, 'index']);
        Route::post('/expense-store', [ExpenseController::class, 'store'])->name('expense.store');
        Route::post('/expense-update/{id}', [ExpenseController::class, 'update']);
        Route::delete('/expense-delete/{id}', [ExpenseController::class, 'destroy']);
        Route::get('/expense-sub-categories/{parentId}', [ExpenseController::class, 'getSubCategories']);
        Route::get('/expense-locations', [ExpenseController::class, 'getLocationsForExpense']);
        Route::get('/expense-suppliers', [ExpenseController::class, 'getSuppliersForExpense']);
        Route::get('/expense-supplier-balance/{supplierId}', [ExpenseController::class, 'getSupplierBalanceHistory']);
        Route::post('/expense-add-payment/{id}', [ExpenseController::class, 'addPayment']);
        Route::get('/expense-payment-history/{id}', [ExpenseController::class, 'getPaymentHistory']);
        Route::get('/expense-payment/{paymentId}', [ExpenseController::class, 'getPayment']);
        Route::put('/expense-payment/{paymentId}', [ExpenseController::class, 'editPayment']);
        Route::delete('/expense-payment/{paymentId}', [ExpenseController::class, 'deletePayment']);
        Route::get('/expense-reports', [ExpenseController::class, 'reports']);
       
        // -------------------- ExpenseParentCategoryController Routes --------------------
        Route::get('/expense-parent-catergory', [ExpenseParentCategoryController::class, 'mainCategory'])->name('expense-parent-catergory');
        Route::get('/expense-parent-catergory-edit/{id}', [ExpenseParentCategoryController::class, 'edit']);
        Route::get('/expense-parent-catergory-get-all', [ExpenseParentCategoryController::class, 'index']);
        Route::get('/expense-parent-categories-dropdown', [ExpenseParentCategoryController::class, 'getForDropdown'])->name('expense-parent-categories.dropdown');
        Route::post('/expense-parent-catergory-store', [ExpenseParentCategoryController::class, 'store'])->name('expense-parent-catergory-store');
        Route::post('/expense-parent-catergory-update/{id}', [ExpenseParentCategoryController::class, 'update']);
        Route::delete('/expense-parent-catergory-delete/{id}', [ExpenseParentCategoryController::class, 'destroy']);

        // -------------------- ExpenseSubCategoryController Routes --------------------
        Route::get('/sub-expense-category', [ExpenseSubCategoryController::class, 'SubCategory'])->name('sub-expense-category');
        Route::get('/sub-expense-category-edit/{id}', [ExpenseSubCategoryController::class, 'edit']);
        Route::get('/sub-expense-category-get-all', [ExpenseSubCategoryController::class, 'index']);
        Route::get('/expense-sub-categories/{parentCategoryId}', [ExpenseSubCategoryController::class, 'getByParentCategory'])->name('expense-sub-categories.by-parent');
        Route::post('/sub-expense-category-store', [ExpenseSubCategoryController::class, 'store'])->name('sub-expense-category-store');
        Route::post('/sub-expense-category-update/{id}', [ExpenseSubCategoryController::class, 'update']);
        Route::delete('/sub-expense-category-delete/{id}', [ExpenseSubCategoryController::class, 'destroy']);

        //Grouped Routes for SalesRep, Vehicle, and Route
        Route::group(['prefix' => 'sales-rep'], function () {
            //vehicle location
            //sales reps
            Route::get('/sales-reps', [SalesRepController::class, 'create'])->name('sales-reps.create');
            //routes
            Route::get('/routes', [RouteController::class, 'create'])->name('routes.create');
            //cities
            Route::get('/cities', [CityController::class, 'create'])->name('cities.create');
            //route-cities
            Route::get('/route-cities', [RouteCityController::class, 'create'])->name('route-cities.create');

            //targets
            Route::get('/targets', [SalesRepTargetController::class, 'create'])->name('targets.create');

            //sales rep targets

            // -------------------- Sales Rep CRUD Routes --------------------
            Route::get('/sales-reps/index', [SalesRepController::class, 'index'])->name('sales-reps.index');
            Route::post('/sales-reps/store', [SalesRepController::class, 'store'])->name('sales-reps.store');
            Route::get('/sales-reps/show/{id}', [SalesRepController::class, 'show'])->name('sales-reps.show');
            Route::put('/sales-reps/update/{id}', [SalesRepController::class, 'update'])->name('sales-reps.update');
            Route::delete('/sales-reps/destroy/{id}', [SalesRepController::class, 'destroy'])->name('sales-reps.destroy');

            // -------------------- Sales Rep Helper Routes --------------------
            Route::get('/sales-reps/available-users', [SalesRepController::class, 'getAvailableUsers'])->name('sales-reps.available-users');
            Route::get('/sales-reps/available-routes', [SalesRepController::class, 'getAvailableRoutes'])->name('sales-reps.available-routes');
            Route::post('/sales-reps/assign-locations', [SalesRepController::class, 'assignUserToLocations'])->name('sales-reps.assign-locations');
            
            // -------------------- Sales Rep POS Routes --------------------
            Route::get('/my-assignments', [SalesRepController::class, 'getMyAssignments'])->name('sales-rep.my-assignments');

        });

               
        // -------------------- ReportController Routes --------------------
        Route::get('/stock-report', [ReportController::class, 'stockHistory'])->name('stock.report');
        Route::get('/account-ledger', [ReportController::class, 'accountLedger'])->name('account.ledger');
        Route::get('/activity-log', [ReportController::class, 'activityLogPage'])->name('activity-log.activityLogPage');
        Route::post('/activity-log/fetch', [ReportController::class, 'fetchActivityLog'])->name('activity-log.fetch');
        
        // Profit & Loss Reports
        Route::get('/profit-loss-report', [ReportController::class, 'profitLossReport'])->name('profit-loss.report');
        Route::post('/profit-loss-data', [ReportController::class, 'profitLossData'])->name('profit-loss.data');
        Route::post('/profit-loss-export-pdf', [ReportController::class, 'profitLossExportPdf'])->name('profit-loss.export.pdf');
        Route::post('/profit-loss-export-excel', [ReportController::class, 'profitLossExportExcel'])->name('profit-loss.export.excel');
        Route::post('/profit-loss-export-csv', [ReportController::class, 'profitLossExportCsv'])->name('profit-loss.export.csv');
        Route::match(['GET', 'POST'], '/profit-loss-product-details/{productId?}', [ReportController::class, 'profitLossProductDetails'])->name('profit-loss.product.details');

       // -------------------- LocationController Routes --------------------
        Route::get('/location', [LocationController::class, 'location'])->name('location');
        Route::get('/location-edit/{id}', [LocationController::class, 'edit']);
        Route::get('/location-get-all', [LocationController::class, 'index']);
        Route::post('/location-store', [LocationController::class, 'store']);
        Route::post('/location-update/{id}', [LocationController::class, 'update']);
        Route::delete('/location-delete/{id}', [LocationController::class, 'destroy']);
        
        // New vehicle and hierarchy routes
        Route::get('/location-parents', [LocationController::class, 'getParentLocations']);
        Route::get('/location-sublocations/{parentId}', [LocationController::class, 'getSublocations']);
        Route::get('/location-by-vehicle-type/{vehicleType}', [LocationController::class, 'getLocationsByVehicleType']);
        Route::get('/location-search-by-vehicle', [LocationController::class, 'searchByVehicleNumber']);
        Route::get('/location-hierarchy/{id}', [LocationController::class, 'getLocationHierarchy']);
       
        // -------------------- Site Setting Routes --------------------
        Route::get('/site-settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/site-settings/update', [SettingController::class, 'update'])->name('settings.update');

        //Salesrep routes

        

         // // -------------------- VariationTitleController Routes --------------------
        // Route::get('/variation-title', [VariationTitleController::class, 'variationTitle'])->name('variation-title');
        // Route::get('/variation-title-edit/{id}', [VariationTitleController::class, 'edit']);
        // Route::get('/variation-title-get-all', [VariationTitleController::class, 'index']);
        // Route::post('/variation-title-store', [VariationTitleController::class, 'store'])->name('variation-title-store');
        // Route::post('/variation-title-update/{id}', [VariationTitleController::class, 'update']);
        // Route::delete('/variation-title-delete/{id}', [VariationTitleController::class, 'destroy']);
        
 // // -------------------- OpeningStockController Routes --------------------
        // Route::get('/import-opening-stock', [OpeningStockController::class, 'importOpeningStock'])->name('import-opening-stock');
        // Route::get('/import-opening-stock-edit/{id}', [OpeningStockController::class, 'edit']);
        // Route::get('/import-opening-stock-get-all', [OpeningStockController::class, 'index']);
        // Route::post('/import-opening-stock-store', [OpeningStockController::class, 'store']);
        // Route::post('/import-opening-stock-update/{id}', [OpeningStockController::class, 'update']);
        // Route::delete('/import-opening-stock-delete/{id}', [OpeningStockController::class, 'destroy']);
        // // Excel Import/Export
        // Route::get('/excel-export-student', [OpeningStockController::class, 'export'])->name('excel-export-student');
        // Route::get('/excel-blank-template-export', [OpeningStockController::class, 'exportBlankTemplate'])->name('excel-blank-template-export');
        // Route::post('/import-opening-stck-excel-store', [OpeningStockController::class, 'importOpeningStockStore']);
        // Route::post('/opening-stock-store', [OpeningStockController::class, 'store'])->name('opening-stock.store');

       
        // // -------------------- CartController Routes --------------------
        // Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
        // Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
        // Route::delete('/cart/remove/{rowId}', [CartController::class, 'remove'])->name('cart.remove');
        // Route::put('/cart/update/{rowId}', [CartController::class, 'update'])->name('cart.update');

         // // -------------------- SalesCommissionAgentsController Routes --------------------
        // Route::get('/sales-commission-agent', [SalesCommissionAgentsController::class, 'SalesCommissionAgents'])->name('sales-commission-agent');
        // Route::get('/sales-commission-agent-edit/{id}', [SalesCommissionAgentsController::class, 'edit']);
        // Route::get('/sales-commission-agent-get-all', [SalesCommissionAgentsController::class, 'index']);
        // Route::post('/sales-commission-agent-store', [SalesCommissionAgentsController::class, 'store'])->name('sales-commission-agent-store');
        // Route::post('/sales-commission-agent-update/{id}', [SalesCommissionAgentsController::class, 'update']);
        // Route::delete('/sales-commission-agent-delete/{id}', [SalesCommissionAgentsController::class, 'destroy']);

        // // -------------------- PrintLabelController Routes --------------------
        // Route::get('/print-label', [PrintLabelController::class, 'printLabel'])->name('print-label');

        // // -------------------- VariationController Routes ------------------
        // Route::get('/variation', [VariationController::class, 'variation'])->name('variation');
        // Route::get('/variation-edit/{id}', [VariationController::class, 'edit']);
        // Route::get('/variation-get-all', [VariationController::class, 'index']);
        // Route::post('/variation-store', [VariationController::class, 'store'])->name('variation-title-store');
        // Route::post('/variation-update/{id}', [VariationController::class, 'update']);
        // Route::delete('/variation-delete/{id}', [VariationController::class, 'destroy']);

        // // -------------------- SellingPriceGroupController Routes --------------------
        // Route::get('/selling-price-group', [SellingPriceGroupController::class, 'sellingPrice'])->name('selling-price-group');
        // Route::get('/selling-price-group-edit/{id}', [SellingPriceGroupController::class, 'edit']);
        // Route::get('/selling-price-group-get-all', [SellingPriceGroupController::class, 'index']);
        // Route::post('/selling-price-group-store', [SellingPriceGroupController::class, 'store'])->name('selling-price-group-store');
        // Route::post('/selling-price-group-update/{id}', [SellingPriceGroupController::class, 'update']);
        // Route::delete('/selling-price-group-delete/{id}', [SellingPriceGroupController::class, 'destroy']);

        
        // // -------------------- CustomerGroupController Routes --------------------
        // Route::get('/customer-group', [CustomerGroupController::class, 'customerGroup'])->name('customer-group');
        // Route::get('/customer-group-edit/{id}', [CustomerGroupController::class, 'edit']);
        // Route::get('/customer-group-get-all', [CustomerGroupController::class, 'index']);
        // Route::post('/customer-group-store', [CustomerGroupController::class, 'store'])->name('customer-group-store');
        // Route::post('/customer-group-update/{id}', [CustomerGroupController::class, 'update']);
        // Route::delete('/customer-group-delete/{id}', [CustomerGroupController::class, 'destroy']);
    });
});