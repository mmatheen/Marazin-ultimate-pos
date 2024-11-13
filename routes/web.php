<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Request;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\WarrantyController;
use App\Http\Controllers\VariationController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PrintLabelController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\MainCategoryController;
use App\Http\Controllers\OpeningStockController;
use App\Http\Controllers\CustomerGroupController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\VariationTitleController;
use App\Http\Controllers\SellingPriceGroupController;


use App\Http\Controllers\ExpenseSubCategoryController;
use App\Http\Controllers\ExpenseParentCategoryController;
use App\Http\Controllers\SalesCommissionAgentsController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

function set_active($route)
{
    if (is_array($route)) {
        return in_array(Request::path(), $route) ? 'active' : '';
    }
    return Request::path() == $route ? 'active' : '';
}

Route::get('/testing', function () {
    //     return view('welcome');
    // $adminRole = Role::findByName('Admin'); // Or 'Super Admin', 'Manager', 'Cashier' as appropriate
    // dd($adminRole->permissions->pluck('name'));
    $role = Auth::user()->location_id;
    dd($role);
});


Route::get('/dashboard', function () {
    return view('includes.dashboards.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__ . '/auth.php';

// without login you can't go to the any page and auth middleware will manage this task

//    Route::group(['middleware' => ['role:Super Admin']], function () {

//});

Route::group(['middleware' => function ($request, $next) {

    // Define your routes with a dynamic role
    $role = Auth::user()->role_name ?? null;
    if ($role) {
        $request->route()->middleware("role:$role");
    }
    return $next($request);
}], function () {


    require __DIR__ . '/role_permission.php';

    //start user route
    Route::get('/user', [UserController::class, 'user'])->name('user');
    Route::get('/user-edit/{id}', [UserController::class, 'edit']);
    Route::get('/user-get-all', [UserController::class, 'index']);
    Route::post('/user-store', [UserController::class, 'store']);
    Route::post('/user-update/{id}', [UserController::class, 'update']);
    Route::delete('/user-delete/{id}', [UserController::class, 'destroy']);
    //stop user route

    //start warranty route
    Route::get('/warranty', [WarrantyController::class, 'warranty'])->name('warranty');
    Route::get('/warranty-edit/{id}', [WarrantyController::class, 'edit']);
    Route::get('/warranty-get-all', [WarrantyController::class, 'index']);
    Route::post('/warranty-store', [WarrantyController::class, 'store'])->name('warranty-store');
    Route::post('/warranty-update/{id}', [WarrantyController::class, 'update']);
    Route::delete('/warranty-delete/{id}', [WarrantyController::class, 'destroy']);
    //stop warranty route

});

//start Guard route start
Route::get('/get-all-details-using-guard', [AuthenticationController::class, 'getDetailsFromGuardDetailsUsingLoginUer']);
Route::get('/update-location', [AuthenticationController::class, 'updateLocation']);
Route::get('/user-location-get-all', [AuthenticationController::class, 'getAlluserDetails']);
//start Guard route end

Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

//start brand route
Route::get('/brand', [BrandController::class, 'brand'])->name('brand');
Route::get('/brand-edit/{id}', [BrandController::class, 'edit']);
Route::get('/brand-get-all', [BrandController::class, 'index']);
Route::post('/brand-store', [BrandController::class, 'store']);
Route::post('/brand-update/{id}', [BrandController::class, 'update']);
Route::delete('/brand-delete/{id}', [BrandController::class, 'destroy']);
//stop  brand route

//start unit route
Route::get('/unit', [UnitController::class, 'unit'])->name('brand');
Route::get('/unit-edit/{id}', [UnitController::class, 'edit']);
Route::get('/unit-get-all', [UnitController::class, 'index']);
Route::post('/unit-store', [UnitController::class, 'store']);
Route::post('/unit-update/{id}', [UnitController::class, 'update']);
Route::delete('/unit-delete/{id}', [UnitController::class, 'destroy']);
//stop  brand route

//start product route
Route::get('/list-product', [ProductController::class, 'product'])->name('list-product');
Route::get('/initial-product-details', [ProductController::class, 'initialProductDetails'])->name('product-details');
Route::get('/add-product', [ProductController::class, 'addProduct'])->name('add-product');
Route::get('/update-price', [ProductController::class, 'updatePrice'])->name('update-price');
Route::get('/import-product', [ProductController::class, 'importProduct'])->name('import-product');
Route::get('/product-get-all', [ProductController::class, 'index']);
Route::post('/product-store', [ProductController::class, 'store']);
Route::get('/product-get-details/{id}', [ProductController::class, 'getProductDetails']);

//stop product route

Route::get('/sub_category-details-get-by-main-category-id/{main_category_id}', [ProductController::class, 'showSubCategoryDetailsUsingByMainCategoryId'])->name('sub_category-details-get-by-main-category-id');

Route::get('/get-brand', [BrandController::class, 'brandDropdown']);
Route::get('/get-unit', [UnitController::class, 'unitDropdown']);



//start SalesCommissionAgents route
Route::get('/sales-commission-agent', [SalesCommissionAgentsController::class, 'SalesCommissionAgents'])->name('sales-commission-agent');
Route::get('/sales-commission-agent-edit/{id}', [SalesCommissionAgentsController::class, 'edit']);
Route::get('/sales-commission-agent-get-all', [SalesCommissionAgentsController::class, 'index']);
Route::post('/sales-commission-agent-store', [SalesCommissionAgentsController::class, 'store'])->name('sales-commission-agent-store');
Route::post('/sales-commission-agent-update/{id}', [SalesCommissionAgentsController::class, 'update']);
Route::delete('/sales-commission-agent-delete/{id}', [SalesCommissionAgentsController::class, 'destroy']);
//stop  SalesCommissionAgents route

//start Print Label route
Route::get('/print-label', [PrintLabelController::class, 'printLabel'])->name('print-label');
//stop  Print Label route

//start variation route
Route::get('/variation', [VariationController::class, 'variation'])->name('variation');
//stop  variation route

//start selling price route
Route::get('/selling-price-group', [SellingPriceGroupController::class, 'sellingPrice'])->name('selling-price-group');
Route::get('/selling-price-group-edit/{id}', [SellingPriceGroupController::class, 'edit']);
Route::get('/selling-price-group-get-all', [SellingPriceGroupController::class, 'index']);
Route::post('/selling-price-group-store', [SellingPriceGroupController::class, 'store'])->name('selling-price-group-store');
Route::post('/selling-price-group-update/{id}', [SellingPriceGroupController::class, 'update']);
Route::delete('/selling-price-group-delete/{id}', [SellingPriceGroupController::class, 'destroy']);
//stop  selling price route

//start unit route
Route::get('/unit', [UnitController::class, 'unit'])->name('unit');
//stop  unit route

//start main catergories route
Route::get('/main-category', [MainCategoryController::class, 'mainCategory'])->name('main-category');
Route::get('/main-category-edit/{id}', [MainCategoryController::class, 'edit']);
Route::get('/main-category-get-all', [MainCategoryController::class, 'index']);
Route::post('/main-category-store', [MainCategoryController::class, 'store'])->name('main-category-store');
Route::post('/main-category-update/{id}', [MainCategoryController::class, 'update']);
Route::delete('/main-category-delete/{id}', [MainCategoryController::class, 'destroy']);
//stop  main catergories route

//start sub catergories route
Route::get('/sub-category', [SubCategoryController::class, 'SubCategory'])->name('sub-category');
Route::get('/sub-category-edit/{id}', [SubCategoryController::class, 'edit']);
Route::get('/sub-category-get-all', [SubCategoryController::class, 'index']);
Route::post('/sub-category-store', [SubCategoryController::class, 'store'])->name('sub-category-store');
Route::post('/sub-category-update/{id}', [SubCategoryController::class, 'update']);
Route::delete('/sub-category-delete/{id}', [SubCategoryController::class, 'destroy']);
//stop  sub catergories route

//start Supplier route
Route::get('/supplier', [SupplierController::class, 'supplier'])->name('supplier');
Route::get('/supplier-edit/{id}', [SupplierController::class, 'edit']);
Route::get('/supplier-get-all', [SupplierController::class, 'index']);
Route::post('/supplier-store', [SupplierController::class, 'store']);
Route::post('/supplier-update/{id}', [SupplierController::class, 'update']);
Route::delete('/supplier-delete/{id}', [SupplierController::class, 'destroy']);
//stop  Supplier route

//start Customer route
Route::get('/customer', [CustomerController::class, 'customer'])->name('customer');
Route::get('/customer-edit/{id}', [SupplierController::class, 'edit']);
Route::get('/customer-get-all', [SupplierController::class, 'index']);
Route::post('/customer-store', [SupplierController::class, 'store']);
Route::post('/customer-update/{id}', [SupplierController::class, 'update']);
Route::delete('/customer-delete/{id}', [SupplierController::class, 'destroy']);
//stop  Customer route

//start sub catergories route
Route::get('/sub-category', [SubCategoryController::class, 'SubCategory'])->name('sub-category');
Route::get('/sub-category-edit/{id}', [SubCategoryController::class, 'edit']);
Route::get('/sub-category-get-all', [SubCategoryController::class, 'index']);
Route::post('/sub-category-store', [SubCategoryController::class, 'store'])->name('sub-category-store');
Route::post('/sub-category-update/{id}', [SubCategoryController::class, 'update']);
Route::delete('/sub-category-delete/{id}', [SubCategoryController::class, 'destroy']);
//stop  sub catergories route

//start CustomerGroup route
Route::get('/customer-group', [CustomerGroupController::class, 'customerGroup'])->name('customer-group');
Route::get('/customer-group-edit/{id}', [CustomerGroupController::class, 'edit']);
Route::get('/customer-group-get-all', [CustomerGroupController::class, 'index']);
Route::post('/customer-group-store', [CustomerGroupController::class, 'store'])->name('customer-group-store');
Route::post('/customer-group-update/{id}', [CustomerGroupController::class, 'update']);
Route::delete('/customer-group-delete/{id}', [CustomerGroupController::class, 'destroy']);
//stop  CustomerGroup route

//start Import Contacts route
Route::get('/import-contact', [ContactController::class, 'importContact'])->name('import-contact');
//stop  Import Contacts route

//start Purchase route
Route::get('/list-purchase', [PurchaseController::class, 'listPurchase'])->name('list-purchase');
Route::get('/add-purchase', [PurchaseController::class, 'addPurchase'])->name('add-purchase');
//stop  Purchase route

//start PurchaseReturn route
Route::get('/purchase-return', [PurchaseReturnController::class, 'purchaseReturn'])->name('purchase-return');
Route::get('/add-purchase-return', [PurchaseReturnController::class, 'addPurchaseReturn'])->name('add-purchase-return');
//stop  PurchaseReturn route

//start Stock transfer route
Route::get('/list-stock-transfer', [StockTransferController::class, 'stockTranfer'])->name('list-stock-transfer');
Route::get('/add-stock-transfer', [StockTransferController::class, 'addStockTransfer'])->name('add-stock-transfer');
//stop  Stock transfer route

//start Sale transfer route
Route::get('/list-sale', [SaleController::class, 'listSale'])->name('list-sale');
Route::get('/add-sale', [SaleController::class, 'addSale'])->name('add-sale');
//stop  Sale transfer route

//start expense-parent route
Route::get('/expense-parent-catergory', [ExpenseParentCategoryController::class, 'mainCategory'])->name('expense-parent-catergory');
Route::get('/expense-parent-catergory-edit/{id}', [ExpenseParentCategoryController::class, 'edit']);
Route::get('/expense-parent-catergory-get-all', [ExpenseParentCategoryController::class, 'index']);
Route::post('/expense-parent-catergory-store', [ExpenseParentCategoryController::class, 'store'])->name('expense-parent-catergory-store');
Route::post('/expense-parent-catergory-update/{id}', [ExpenseParentCategoryController::class, 'update']);
Route::delete('/expense-parent-catergory-delete/{id}', [ExpenseParentCategoryController::class, 'destroy']);
//stop  expense-parent route

//start sub Expense Category route
Route::get('/sub-expense-category', [ExpenseSubCategoryController::class, 'SubCategory'])->name('sub-expense-category');
Route::get('/sub-expense-category-edit/{id}', [ExpenseSubCategoryController::class, 'edit']);
Route::get('/sub-expense-category-get-all', [ExpenseSubCategoryController::class, 'index']);
Route::post('/sub-expense-category-store', [ExpenseSubCategoryController::class, 'store'])->name('sub-expense-category-store');
Route::post('/sub-expense-category-update/{id}', [ExpenseSubCategoryController::class, 'update']);
Route::delete('/sub-expense-category-delete/{id}', [ExpenseSubCategoryController::class, 'destroy']);
//stop  sub Expense Category route

//start variation title  route
Route::get('/variation-title', [VariationTitleController::class, 'variationTitle'])->name('variation-title');
Route::get('/variation-title-edit/{id}', [VariationTitleController::class, 'edit']);
Route::get('/variation-title-get-all', [VariationTitleController::class, 'index']);
Route::post('/variation-title-store', [VariationTitleController::class, 'store'])->name('variation-title-store');
Route::post('/variation-title-update/{id}', [VariationTitleController::class, 'update']);
Route::delete('/variation-title-delete/{id}', [VariationTitleController::class, 'destroy']);
//stop  variation title  route

//start variation route
Route::get('/variation', [VariationController::class, 'variation'])->name('variation');
Route::get('/variation-edit/{id}', [VariationController::class, 'edit']);
Route::get('/variation-get-all', [VariationController::class, 'index']);
Route::post('/variation-store', [VariationController::class, 'store'])->name('variation-title-store');
Route::post('/variation-update/{id}', [VariationController::class, 'update']);
Route::delete('/variation-delete/{id}', [VariationController::class, 'destroy']);
//stop  variation route

//start location route
Route::get('/location', [LocationController::class, 'location'])->name('location');
Route::get('/location-edit/{id}', [LocationController::class, 'edit']);
Route::get('/location-get-all', [LocationController::class, 'index']);
Route::post('/location-store', [LocationController::class, 'store']);
Route::post('/location-update/{id}', [LocationController::class, 'update']);
Route::delete('/location-delete/{id}', [LocationController::class, 'destroy']);
//stop  location route

//start import-opening-stock route
Route::get('/import-opening-stock', [OpeningStockController::class, 'importOpeningStock'])->name('import-opening-stock');
Route::get('/import-opening-stock-edit/{id}', [OpeningStockController::class, 'edit']);
Route::get('/import-opening-stock-get-all', [OpeningStockController::class, 'index']);
Route::post('/import-opening-stock-store', [OpeningStockController::class, 'store']);
Route::post('/import-opening-stock-update/{id}', [OpeningStockController::class, 'update']);
Route::delete('/import-opening-stock-delete/{id}', [OpeningStockController::class, 'destroy']);
//stop  import-opening-stock route

// Excel import/export routes
Route::get('/excel-export-student', [OpeningStockController::class, 'export'])->name('excel-export-student');
Route::get('/excel-blank-template-export', [OpeningStockController::class, 'exportBlankTemplate'])->name('excel-blank-template-export');
Route::post('/import-opening-stck-excel-store', [OpeningStockController::class, 'importOpeningStockStore']);
