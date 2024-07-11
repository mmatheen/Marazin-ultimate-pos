<?php

use App\Http\Controllers\WarrantyController;
use App\Http\Controllers\CurrencyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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


Route::post('/currecy-store', [CurrencyController::class, 'store']);
Route::get('/currecy-get-all', [CurrencyController::class, 'index']);
Route::get('/currecy-edit/{id}', [CurrencyController::class, 'edit']);
Route::post('/currecy-update/{id}', [CurrencyController::class, 'update']);
Route::delete('/currecy-delete/{id}', [CurrencyController::class, 'destroy']);
