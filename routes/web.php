<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\AdminController;
use App\Http\Controllers\Web\Admin\CategoryController;
use App\Http\Controllers\Web\Admin\ProductController;
use App\Http\Controllers\Web\Admin\DeliveryPartnerController;
use App\Http\Controllers\Web\Admin\OrderController;

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
        Route::get('/dashboard', [AdminController::class, 'index'])->name('admin.dashboard.alias');

        // Placeholder routes for navigation (to be implemented later)
        Route::get('/users', [AdminController::class, 'index'])->name('admin.users');

        // Orders Management
        Route::get('/orders', [OrderController::class, 'index'])->name('admin.orders');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('admin.orders.show');
        Route::post('/orders/{order}/assign', [OrderController::class, 'assignPartner'])->name('admin.orders.assign');
        Route::delete('/orders/{order}/unassign', [OrderController::class, 'unassignPartner'])->name('admin.orders.unassign');
        Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('admin.orders.update-status');

        // Categories CRUD
        Route::resource('categories', CategoryController::class)->names('admin.categories');

        // Products CRUD
        Route::resource('products', ProductController::class)->names('admin.products');

        // Delivery Partners CRUD
        Route::resource('delivery-partners', DeliveryPartnerController::class)->names('admin.delivery-partners');
        Route::patch('delivery-partners/{delivery_partner}/toggle-status', [DeliveryPartnerController::class, 'toggleStatus'])
            ->name('admin.delivery-partners.toggle-status');
        Route::patch('delivery-partners/{delivery_partner}/clear-assignment', [DeliveryPartnerController::class, 'clearAssignment'])
            ->name('admin.delivery-partners.clear-assignment');
    });
});
