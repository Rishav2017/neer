<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\AdminController;
use App\Http\Controllers\Web\Admin\CategoryController;
use App\Http\Controllers\Web\Admin\ProductController;

Route::get('/', function () {
    return view('welcome');
});

// Default login route (redirects to admin login)
Route::get('login', function () {
    return redirect()->route('admin.login');
})->name('login');

// Admin Authentication Routes
Route::prefix('admin')->group(function () {
    // Login routes (accessible without authentication)
    Route::get('login', [AuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('login', [AuthController::class, 'login']);

    // Logout route
    Route::post('logout', [AuthController::class, 'logout'])->name('admin.logout');

    // Protected admin routes
    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('admin.dashboard');

        // Placeholder routes for navigation (to be implemented later)
        Route::get('/users', [AdminController::class, 'index'])->name('admin.users');
        Route::get('/orders', [AdminController::class, 'index'])->name('admin.orders');

        // Categories CRUD
        Route::resource('categories', CategoryController::class)->names('admin.categories');

        // Products CRUD
        Route::resource('products', ProductController::class)->names('admin.products');
    });
});
