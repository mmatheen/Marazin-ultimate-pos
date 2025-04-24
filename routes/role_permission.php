<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoleController;

//start role route
Route::get('/role', [RoleController::class, 'role'])->name('role');
Route::get('/role-edit/{id}', [RoleController::class, 'edit']);
Route::get('/role-get-all', [RoleController::class, 'index']);
Route::post('/role-store', [RoleController::class, 'store'])->name('role-store');
Route::post('/role-update/{id}', [RoleController::class, 'update']);
Route::delete('/role-delete/{id}', [RoleController::class, 'destroy']);
//stop  role route
