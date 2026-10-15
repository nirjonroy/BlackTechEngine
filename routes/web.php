<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\RolePermissionController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin Unauthenticated Routes
Route::middleware('guest:admin')->group(function () {
    Route::get('/admin', [AuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/admin/login', [AuthController::class, 'login']);
});

// Admin Authenticated Routes
Route::middleware('auth:admin')->group(function () {
    Route::get('/admin/dashboard', function () {
        return view('admin.dashboard'); // Create this view later
    })->name('admin.dashboard');
    
    Route::post('/admin/logout', [AuthController::class, 'logout'])->name('admin.logout');

    Route::get('/admin/role-permission', [RolePermissionController::class, 'index'])->name('admin.role-permission.index');
    Route::get('/admin/role-permission/admin/create', [RolePermissionController::class, 'createAdmin'])->name('admin.role-permission.admin.create');
    Route::post('/admin/role-permission/admin/store', [RolePermissionController::class, 'storeAdmin'])->name('admin.role-permission.admin.store');
    Route::get('/admin/role-permission/role/{role}/edit', [RolePermissionController::class, 'editRole'])->name('admin.role-permission.role.edit');
    Route::post('/admin/role-permission/role/{role}/update', [RolePermissionController::class, 'updateRole'])->name('admin.role-permission.role.update');
    Route::get('/admin/role-permission/role/create', [RolePermissionController::class, 'createRole'])->name('admin.role-permission.role.create');
Route::post('/admin/role-permission/role/store', [RolePermissionController::class, 'storeRole'])->name('admin.role-permission.role.store');
    });

require __DIR__.'/auth.php';
