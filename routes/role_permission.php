<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RoleInPermissionController;

//start role route
Route::get('/role', [RoleController::class, 'role'])->name('role');
Route::get('/role-edit/{id}', [RoleController::class, 'edit']);
Route::get('/role-get-all', [RoleController::class, 'index']);
Route::post('/role-store', [RoleController::class, 'store'])->name('role-store');
Route::post('/role-update/{id}', [RoleController::class, 'update']);
Route::delete('/role-delete/{id}', [RoleController::class, 'destroy']);
//stop  role route

Route::get('/group-role-and-permission-view', [RoleInPermissionController::class, 'groupRoleAndPermissionView'])->name('group-role-and-permission-view');
Route::get('/group-role-and-permission', [RoleInPermissionController::class, 'groupRoleAndPermission'])->name('group-role-and-permission');
Route::get('/role-and-permission-edit/{role_id}', [RoleInPermissionController::class, 'edit']);
Route::post('/role-and-permission-store', [RoleInPermissionController::class, 'store'])->name('role-and-permission-store');
Route::get('/group-and-permission-all', [RoleInPermissionController::class, 'index'])->name('group-and-permission-all');
Route::get('/role-and-permission-all', [RoleInPermissionController::class, 'groupRoleAndPermissionList'])->name('role-and-permission-all');
Route::delete('/role-and-permission-delete/{role_id}', [RoleInPermissionController::class, 'destroy']);
//stop role route

