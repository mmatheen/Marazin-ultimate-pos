<?php

use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CatergoriesController;
use App\Http\Controllers\ContactsController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerGroupController;
use App\Http\Controllers\PrintLabelController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\SalesCommissionAgentsController;
use App\Http\Controllers\SellingPriceController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VariationController;
use App\Http\Controllers\WarrantyController;
use Illuminate\Support\Facades\Route;

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

Route::get('/', function () {
    return view('welcome');
});


Route::get('/dashboard', [AuthenticationController::class, 'dashboard'])->name('dashboard');
Route::get('/warrantyList', [WarrantyController::class, 'warrantyList'])->name('warrantyList');

//start warranty route
Route::get('/add-warranty', [WarrantyController::class, 'warranty'])->name('add-warranty');
Route::get('/warranty-edit/{id}', [WarrantyController::class, 'edit']);
Route::get('/warranty-get-all', [WarrantyController::class, 'index']);
Route::post('/warranty-store', [WarrantyController::class, 'store']);
Route::post('/warranty-update/{id}', [WarrantyController::class, 'update']);
Route::delete('/warranty-delete/{id}', [WarrantyController::class, 'destroy']);
//stop warranty route

//start product route
Route::get('/productList', [ProductController::class, 'warrantyList'])->name('product_list');
Route::get('/add-product', [ProductController::class, 'product'])->name('add-product');
Route::get('/update-price', [ProductController::class, 'updatePrice'])->name('updatePrice');
Route::get('/importproduct', [ProductController::class, 'importProduct'])->name('import_products');

Route::get('/product-edit/{id}', [ProductController::class, 'edit']);
Route::get('/product-get-all', [ProductController::class, 'index']);
Route::post('/product-store', [ProductController::class, 'store']);
Route::post('/product-update/{id}', [ProductController::class, 'update']);
Route::delete('/product-delete/{id}', [ProductController::class, 'destroy']);
//stop product route


//start user route
Route::get('/userList', [UserController::class, 'UserList'])->name('UserList');
Route::get('/add-user', [UserController::class, 'AddUser'])->name('add-user');
//stop product route

//start user route
Route::get('/rolesList', [RolesController::class, 'RoleList'])->name('RoleList');
Route::get('/add-roles', [RolesController::class, 'AddRole'])->name('add-role');
//stop product route

//start SalesCommissionAgents route
Route::get('/SalesCommissionList', [SalesCommissionAgentsController::class, 'SalesCommissionList'])->name('SalesCommissionList');
//stop  SalesCommissionAgents route

//start Print Label route
Route::get('/printlabel', [PrintLabelController::class, 'printLabel'])->name('printLabel');
//stop  Print Label route

//start variation route
Route::get('/variatiuonlist', [VariationController::class, 'variatiuonList'])->name('variatiuonList');
//stop  variation route

//start import opening route
Route::get('/importopeningstock', [StockController::class, 'importOpeningStock'])->name('importopeningstock');
//stop  import opening route

//start selling price route
Route::get('/sellingpricelist', [SellingPriceController::class, 'SellingPriceList'])->name('sellingpricelist');
//stop  selling price route


//start unit route
Route::get('/unitlist', [UnitController::class, 'UnitList'])->name('unitlist');
//stop  unit route

//start catergories route
Route::get('/catergoriesList', [CatergoriesController::class, 'CatergoriesList'])->name('catergoriesList');
//stop  catergories route

//start brand route
Route::get('/brandList', [BrandController::class, 'BrandList'])->name('brandList');
//stop  brand route

//start Supplier route
Route::get('/supplierList', [SupplierController::class, 'SupplierList'])->name('SupplierList');
//stop  Supplier route

//start Customer route
Route::get('/customerList', [CustomerController::class, 'CustomerList'])->name('CustomerList');
//stop  Customer route

//start CustomerGroup route
Route::get('/customerGroupList', [CustomerGroupController::class, 'CustomerGroupList'])->name('CustomerGroupList');
//stop  CustomerGroup route

//start Import Contacts route
Route::get('/importContacts', [ContactsController::class, 'ImportContacts'])->name('ImportContacts');
//stop  Import Contacts route

