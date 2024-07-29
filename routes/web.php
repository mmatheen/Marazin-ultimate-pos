<?php

use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerGroupController;
use App\Http\Controllers\PrintLabelController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SalesCommissionAgentController;
use App\Http\Controllers\SellingPriceController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VariationController;
use App\Http\Controllers\WarrantyController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Request;
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

Route::get('/', function () {
    return view('welcome');
});


Route::get('/dashboard', [AuthenticationController::class, 'dashboard'])->name('dashboard');

//start warranty route
Route::get('/warranty', [WarrantyController::class, 'warranty'])->name('warranty');
Route::get('/warranty-edit/{id}', [WarrantyController::class, 'edit']);
Route::get('/warranty-get-all', [WarrantyController::class, 'index']);
Route::post('/warranty-store', [WarrantyController::class, 'store']);
Route::post('/warranty-update/{id}', [WarrantyController::class, 'update']);
Route::delete('/warranty-delete/{id}', [WarrantyController::class, 'destroy']);
//stop warranty route

//start product route
Route::get('/list-product', [ProductController::class, 'product'])->name('list-product');
Route::get('/add-product', [ProductController::class, 'addProduct'])->name('add-product');
Route::get('/update-price', [ProductController::class, 'updatePrice'])->name('update-price');
Route::get('/import-product', [ProductController::class, 'importProduct'])->name('import-product');

Route::get('/product-edit/{id}', [ProductController::class, 'edit']);
Route::get('/product-get-all', [ProductController::class, 'index']);
Route::post('/product-store', [ProductController::class, 'store']);
Route::post('/product-update/{id}', [ProductController::class, 'update']);
Route::delete('/product-delete/{id}', [ProductController::class, 'destroy']);
//stop product route


//start user route
Route::get('/user', [UserController::class, 'user'])->name('user');
Route::get('/add-user', [UserController::class, 'addUser'])->name('add-user');
//stop product route

//start user route
Route::get('/role', [RoleController::class, 'role'])->name('role');
Route::get('/add-role', [RoleController::class, 'addRole'])->name('add-role');
//stop product route

//start SalesCommissionAgents route
Route::get('/sales-commission', [SalesCommissionAgentController::class, 'salesCommission'])->name('sales-commission');
//stop  SalesCommissionAgents route

//start Print Label route
Route::get('/print-label', [PrintLabelController::class, 'printLabel'])->name('print-label');
//stop  Print Label route

//start variation route
Route::get('/variation', [VariationController::class, 'variation'])->name('variation');
//stop  variation route

//start import opening route
Route::get('/import-opening-stock', [StockController::class, 'importOpeningStock'])->name('import-opening-stock');
//stop  import opening route

//start selling price route
Route::get('/selling-price-group', [SellingPriceController::class, 'sellingPrice'])->name('selling-price-group');
//stop  selling price route


//start unit route
Route::get('/unit', [UnitController::class, 'unit'])->name('unit');
//stop  unit route

//start catergories route
Route::get('/category', [CategoryController::class, 'category'])->name('category');
//stop  catergories route

//start brand route
Route::get('/brand', [BrandController::class, 'brand'])->name('brand');
//stop  brand route

//start Supplier route
Route::get('/supplier', [SupplierController::class, 'supplier'])->name('supplier');
//stop  Supplier route

//start Customer route
Route::get('/customer', [CustomerController::class, 'customer'])->name('customer');
//stop  Customer route

//start CustomerGroup route
Route::get('/customer-group', [CustomerGroupController::class, 'customerGroup'])->name('customer-group');
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
