<?php

use App\Http\Controllers\AuthenticationController;
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
