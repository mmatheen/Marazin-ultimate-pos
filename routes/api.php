<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\WarrantyController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SellingPriceGroupController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MainCategoryController;
use App\Http\Controllers\SubCategoryController;

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
Route::post('/warranty-update/{id}', [WarrantyController::class, 'update']);
Route::delete('/warranty-delete/{id}', [WarrantyController::class, 'destroy']);


//start brand route
Route::get('/brand', [BrandController::class, 'brand'])->name('brand');
Route::get('/brand-edit/{id}', [BrandController::class, 'edit']);
Route::get('/brand-get-all', [BrandController::class, 'index']);
Route::post('/brand-store', [BrandController::class, 'store'])->name('brand-store');
Route::post('/brand-update/{id}', [BrandController::class, 'update']);
Route::delete('/brand-delete/{id}', [BrandController::class, 'destroy']);
//stop  brand route

//start selling price route
Route::get('/selling-price-group', [SellingPriceGroupController::class, 'sellingPrice'])->name('selling-price-group');
Route::get('/selling-price-group-edit/{id}', [SellingPriceGroupController::class, 'edit']);
Route::get('/selling-price-group-get-all', [SellingPriceGroupController::class, 'index']);
Route::post('/selling-price-group-store', [SellingPriceGroupController::class, 'store'])->name('selling-price-group-store');
Route::post('/selling-price-group-update/{id}', [SellingPriceGroupController::class, 'update']);
Route::delete('/selling-price-group-delete/{id}', [SellingPriceGroupController::class, 'destroy']);
//stop  selling price route

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

Route::get('/get-brand', [BrandController::class, 'brandDropdown']);
