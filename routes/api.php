<?php

use Illuminate\Http\Request;
use App\Models\VariationTitle;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\WarrantyController;
use App\Http\Controllers\VariationController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\MainCategoryController;
use App\Http\Controllers\OpeningStockController;
use App\Http\Controllers\CustomerGroupController;
use App\Http\Controllers\VariationTitleController;
use App\Http\Controllers\RoleInPermissionController;
use App\Http\Controllers\SellingPriceGroupController;
use App\Http\Controllers\ExpenseSubCategoryController;
use App\Http\Controllers\ExpenseParentCategoryController;
use App\Http\Controllers\SellController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchaseReturnController;

use App\Http\Controllers\SaleController;
use App\Http\Controllers\SaleReturnController;
use App\Http\Controllers\SalesReturnController;
use App\Http\Controllers\StockTransferController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('/warranty-store', [WarrantyController::class, 'store']);
Route::get('/warranty-get-all', [WarrantyController::class, 'index']);
Route::get('/warranty-edit/{id}', [WarrantyController::class, 'edit']);
Route::put('/warranty-update/{id}', [WarrantyController::class, 'update']);
Route::delete('/warranty-delete/{id}', [WarrantyController::class, 'destroy']);


//start brand route
Route::get('/brand-edit/{id}', [BrandController::class, 'edit']);
Route::get('/brand-get-all', [BrandController::class, 'index']);
Route::post('/brand-store', [BrandController::class, 'store'])->name('brand-store');
Route::post('/brand-update/{id}', [BrandController::class, 'update']);
Route::delete('/brand-delete/{id}', [BrandController::class, 'destroy']);
//stop  brand route

//start selling price route
Route::get('/selling-price-group-edit/{id}', [SellingPriceGroupController::class, 'edit']);
Route::get('/selling-price-group-get-all', [SellingPriceGroupController::class, 'index']);
Route::post('/selling-price-group-store', [SellingPriceGroupController::class, 'store'])->name('selling-price-group-store');
Route::post('/selling-price-group-update/{id}', [SellingPriceGroupController::class, 'update']);
Route::delete('/selling-price-group-delete/{id}', [SellingPriceGroupController::class, 'destroy']);
//stop  selling price route

//start main catergories route
Route::get('/main-category-edit/{id}', [MainCategoryController::class, 'edit']);
Route::get('/main-category-get-all', [MainCategoryController::class, 'index']);
Route::post('/main-category-store', [MainCategoryController::class, 'store'])->name('main-category-store');
Route::post('/main-category-update/{id}', [MainCategoryController::class, 'update']);
Route::delete('/main-category-delete/{id}', [MainCategoryController::class, 'destroy']);
//stop  main catergories route

//start sub catergories route
Route::get('/sub-category-edit/{id}', [SubCategoryController::class, 'edit']);
Route::get('/sub-category-get-all', [SubCategoryController::class, 'index']);
Route::post('/sub-category-store', [SubCategoryController::class, 'store'])->name('sub-category-store');
Route::post('/sub-category-update/{id}', [SubCategoryController::class, 'update']);
Route::delete('/sub-category-delete/{id}', [SubCategoryController::class, 'destroy']);
//stop  sub catergories route

//start CustomerGroup route
Route::get('/customer-group-edit/{id}', [CustomerGroupController::class, 'edit']);
Route::get('/customer-group-get-all', [CustomerGroupController::class, 'index']);
Route::post('/customer-group-store', [CustomerGroupController::class, 'store'])->name('customer-group-store');
Route::post('/customer-group-update/{id}', [CustomerGroupController::class, 'update']);
Route::delete('/customer-group-delete/{id}', [CustomerGroupController::class, 'destroy']);
//stop  CustomerGroup route

//start expense-parent route
Route::get('/expense-parent-catergory-edit/{id}', [ExpenseParentCategoryController::class, 'edit']);
Route::get('/expense-parent-catergory-get-all', [ExpenseParentCategoryController::class, 'index']);
Route::post('/expense-parent-catergory-store', [ExpenseParentCategoryController::class, 'store'])->name('expense-parent-catergory-store');
Route::post('/expense-parent-catergory-update/{id}', [ExpenseParentCategoryController::class, 'update']);
Route::delete('/expense-parent-catergory-delete/{id}', [ExpenseParentCategoryController::class, 'destroy']);
//stop  expense-parent route

//start sub Expense Category route
Route::get('/sub-expense-category-edit/{id}', [ExpenseSubCategoryController::class, 'edit']);
Route::get('/sub-expense-category-get-all', [ExpenseSubCategoryController::class, 'index']);
Route::post('/sub-expense-category-store', [ExpenseSubCategoryController::class, 'store'])->name('sub-expense-category-store');
Route::post('/sub-expense-category-update/{id}', [ExpenseSubCategoryController::class, 'update']);
Route::delete('/sub-expense-category-delete/{id}', [ExpenseSubCategoryController::class, 'destroy']);
//stop  sub Expense Category route

//start variation title  route
Route::get('/variation-title-edit/{id}', [VariationTitleController::class, 'edit']);
Route::get('/variation-title-get-all', [VariationTitleController::class, 'index']);
Route::post('/variation-title-store', [VariationTitleController::class, 'store'])->name('variation-title-store');
Route::post('/variation-title-update/{id}', [VariationTitleController::class, 'update']);
Route::delete('/variation-title-delete/{id}', [VariationTitleController::class, 'destroy']);
//stop  variation title  route

//start variation route
Route::get('/variation-edit/{id}', [VariationController::class, 'edit']);
Route::get('/variation-get-all', [VariationController::class, 'index']);
Route::post('/variation-store', [VariationController::class, 'store'])->name('variation-title-store');
Route::post('/variation-update/{id}', [VariationController::class, 'update']);
Route::delete('/variation-delete/{id}', [VariationController::class, 'destroy']);
//stop  variation route

Route::get('/get-brand', [BrandController::class, 'brandDropdown']);

//start role route
Route::get('/role-edit/{id}', [RoleController::class, 'edit']);
Route::get('/role-get-all', [RoleController::class, 'index']);
Route::post('/role-store', [RoleController::class, 'store'])->name('role-store');
Route::post('/role-update/{id}', [RoleController::class, 'update']);
Route::delete('/role-delete/{id}', [RoleController::class, 'destroy']);
//stop  role route


//start Supplier route
Route::get('/supplier-edit/{id}', [SupplierController::class, 'edit']);
Route::get('/supplier-get-all', [SupplierController::class, 'index']);
Route::post('/supplier-store', [SupplierController::class, 'store']);
Route::post('/supplier-update/{id}', [SupplierController::class, 'update']);
Route::delete('/supplier-delete/{id}', [SupplierController::class, 'destroy']);
//stop  Supplier route

//start Customer route
Route::get('/customer-edit/{id}', [SupplierController::class, 'edit']);
Route::get('/customer-get-all', [SupplierController::class, 'index']);
Route::post('/customer-store', [SupplierController::class, 'store']);
Route::post('/customer-update/{id}', [SupplierController::class, 'update']);
Route::delete('/customer-delete/{id}', [SupplierController::class, 'destroy']);
//stop  Customer route

//start Customer route
Route::get('/customer-edit/{id}', [CustomerController::class, 'edit']);
Route::get('/customer-get-all', [CustomerController::class, 'index']);
Route::post('/customer-store', [CustomerController::class, 'store']);
Route::post('/customer-update/{id}', [CustomerController::class, 'update']);
Route::delete('/customer-delete/{id}', [CustomerController::class, 'destroy']);
//stop  Customer route

//start location route
Route::get('/location', [LocationController::class, 'location']);
Route::get('/location-edit/{id}', [LocationController::class, 'edit']);
Route::get('/location-get-all', [LocationController::class, 'index']);
Route::post('/location-store', [LocationController::class, 'store']);
Route::post('/location-update/{id}', [LocationController::class, 'update']);
Route::delete('/location-delete/{id}', [LocationController::class, 'destroy']);
//stop  location route

//start import-opening-stock route
Route::get('/import-opening-stock', [OpeningStockController::class, 'importOpeningStock'])->name('importOpeningStock');
Route::get('/import-opening-stock-edit/{id}', [OpeningStockController::class, 'edit']);
Route::get('/import-opening-stock-get-all', [OpeningStockController::class, 'index']);
Route::post('/import-opening-stock-store', [OpeningStockController::class, 'store']);
Route::post('/import-opening-stock-update/{id}', [OpeningStockController::class, 'update']);
Route::delete('/import-opening-stock-delete/{id}', [OpeningStockController::class, 'destroy']);
//stop  import-opening-stock route

//start product route
Route::get('/list-product', [ProductController::class, 'product'])->name('list-product');
Route::get('/add-product', [ProductController::class, 'addProduct'])->name('add-product');
Route::get('/update-price', [ProductController::class, 'updatePrice'])->name('update-price');
Route::get('/import-product', [ProductController::class, 'importProduct'])->name('import-product');
Route::get('/product-get-all', [ProductController::class, 'index']);
Route::post('/product-store', [ProductController::class, 'store']);
//stop product route

Route::get('/group-role-and-permission-view', [RoleInPermissionController::class, 'groupRoleAndPermissionView'])->name('group-role-and-permission-view');
Route::get('/group-role-and-permission', [RoleInPermissionController::class, 'groupRoleAndPermission'])->name('group-role-and-permission');
Route::get('/role-and-permission-edit/{role_id}', [RoleInPermissionController::class, 'edit']);
Route::post('/role-and-permission-store', [RoleInPermissionController::class, 'store'])->name('role-and-permission-store');
Route::get('/group-and-permission-all', [RoleInPermissionController::class, 'index'])->name('group-and-permission-all');
Route::get('/role-and-permission-all', [RoleInPermissionController::class, 'groupRoleAndPermissionList'])->name('role-and-permission-all');
Route::delete('/role-and-permission-delete/{role_id}', [RoleInPermissionController::class, 'destroy']);
//stop role route

  //start unit route
  Route::get('/unit', [UnitController::class, 'unit'])->name('brand');
  Route::get('/unit-edit/{id}', [UnitController::class, 'edit']);
  Route::get('/unit-get-all', [UnitController::class, 'index']);
  Route::post('/unit-store', [UnitController::class, 'store']);
  Route::post('/unit-update/{id}', [UnitController::class, 'update']);
  Route::delete('/unit-delete/{id}', [UnitController::class, 'destroy']);
  //stop  brand route

  Route::post('/product-update/{id}', [ProductController::class, 'UpdateProduct']);
  Route::get('/edit-product/{id}', [ProductController::class, 'EditProduct'])->name('edit-product');


  // Store a new purchase
  // Store a new purchase
  Route::post('/purchases/store', [PurchaseController::class, 'storeOrUpdate']);
  Route::post('/purchases/update/{id}', [PurchaseController::class, 'storeOrUpdate']);
  Route::get('/purchase/edit/{id}', [PurchaseController::class, 'editPurchase']);
  Route::get('/get-all-purchases', [PurchaseController::class, 'getAllPurchase']);
  Route::get('/get-all-purchases-product/{id }', [PurchaseController::class, 'getAllPurchaseProduct']);
  Route::get('/purchase-products-by-supplier/{supplierId}', [PurchaseController::class, 'getPurchaseProductsBySupplier']);

Route::get('/purchase-returns/get-product-details/{purchaseId}/{supplierId}', [PurchaseReturnController::class, 'getProductDetails']);


Route::get('purchase_returns', [PurchaseReturnController::class, 'getAllPurchaseReturns']);
Route::get('purchase_returns/{id}', [PurchaseReturnController::class, 'getPurchaseReturns']);
Route::get('purchase_return/edit/{id}', [PurchaseReturnController::class, 'edit']);
Route::post('/purchase-return/store', [PurchaseReturnController::class, 'storeOrUpdate']);
Route::post('/purchase-return/update/{id}', [PurchaseReturnController::class, 'storeOrUpdate']);


Route::post('/sales/store', [SaleController::class, 'storeOrUpdate']);
Route::post('/sales/update/{id}', [SaleController::class, 'storeOrUpdate']);
Route::get('/opening-stock/{productId}', [ProductController::class, 'showOpeningStock'])->name('opening.stock');


Route::post('/sales-returns/store', [SaleReturnController::class, 'store']);
Route::get('/sales/{invoiceNo}', [SaleController::class, 'getSaleByInvoiceNo']);
Route::get('/search/sales', [SaleController::class, 'searchSales']);



Route::get('sales', [SaleController::class, 'index'])->name('sales.index');
Route::get('sales_details/{id}', [SaleController::class, 'selesDetails']);
Route::get('sales/edit/{id}', [SaleController::class, 'edit'])->name('sales.edit');
Route::put('sales/{id}', [SaleController::class, 'update'])->name('sales.update');
Route::delete('sales/{id}', [SaleController::class, 'destroy'])->name('sales.destroy');



  //start Stock transfer route
  Route::get('/list-stock-transfer', [StockTransferController::class, 'stockTransfer'])->name('list-stock-transfer');
  Route::get('/add-stock-transfer', [StockTransferController::class, 'addStockTransfer'])->name('add-stock-transfer');
  // For creating a new stock transfer
  Route::post('/stock-transfer/store', [StockTransferController::class, 'storeOrUpdate']);
  Route::put('/stock-transfer/update/{id}', [StockTransferController::class, 'storeOrUpdate']);
  Route::get('/edit-stock-transfer/{id}', [StockTransferController::class, 'edit']);

use App\Http\Controllers\StockAdjustmentController;

// Route::post('/stock-adjustment/store', [StockAdjustmentController::class, 'store']);
Route::get('/stock-adjustments', [StockAdjustmentController::class, 'index'])->name('stock-adjustments.index');
Route::get('/edit-stock-adjustment/{id}', [StockAdjustmentController::class, 'edit'])->name('stock-adjustments.edit');
// Route::put('/stock-adjustments/{id}', [StockAdjustmentController::class, 'update'])->name('stock-adjustments.update');
Route::get('/stock-adjustments/{id}', [StockAdjustmentController::class, 'show'])->name('stock-adjustments.show');
// For creating a new stock adjustment
Route::post('/stock-adjustment/store', [StockAdjustmentController::class, 'storeOrUpdate']);

// For updating an existing stock adjustment
Route::put('/stock-adjustment/update/{id}', [StockAdjustmentController::class, 'storeOrUpdate']);
