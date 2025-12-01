<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PaymentWebhookController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\Admin\DeliveryPartnerController;
use App\Http\Controllers\Api\V1\Admin\OrderManagementController;
use App\Http\Controllers\Api\V1\Admin\AdminDashboardController;

// Public routes
Route::prefix('auth')->group(function () {
  Route::post('login', [AuthController::class, 'login']);
  Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

// Public category and product listing
Route::get('categories', [CategoryController::class, 'index']);
Route::get('products', [ProductController::class, 'index']);

// Payment webhooks (no auth - verified by signature)
Route::prefix('payments')->group(function () {
  Route::post('webhook', [PaymentWebhookController::class, 'handleRazorpay']);
  Route::post('webhook/{gateway}', [PaymentWebhookController::class, 'handle']);
});

// Admin routes (require admin role)
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
  // Category management
  Route::post('categories', [CategoryController::class, 'store']);
  Route::post('categories/{id}/subcategories', [CategoryController::class, 'storeSubcategory']);
  Route::put('categories/{id}', [CategoryController::class, 'update']);
  Route::delete('categories/{id}', [CategoryController::class, 'destroy']);

  // Product management
  Route::post('products', [ProductController::class, 'store']);
  Route::put('products/{id}', [ProductController::class, 'update']);
  Route::delete('products/{id}', [ProductController::class, 'destroy']);

  // Delivery Partner management
  Route::get('delivery-partners', [DeliveryPartnerController::class, 'index']);
  Route::post('delivery-partners', [DeliveryPartnerController::class, 'store']);
  Route::get('delivery-partners/available', [DeliveryPartnerController::class, 'available']);
  Route::get('delivery-partners/{deliveryPartner}', [DeliveryPartnerController::class, 'show']);
  Route::put('delivery-partners/{deliveryPartner}', [DeliveryPartnerController::class, 'update']);
  Route::delete('delivery-partners/{deliveryPartner}', [DeliveryPartnerController::class, 'destroy']);
  Route::patch('delivery-partners/{deliveryPartner}/location', [DeliveryPartnerController::class, 'updateLocation']);

  // Order Management
  Route::get('orders', [OrderManagementController::class, 'index']);
  Route::get('orders/statistics', [OrderManagementController::class, 'statistics']);
  Route::get('orders/needs-assignment', [OrderManagementController::class, 'needsAssignment']);
  Route::get('orders/{order}', [OrderManagementController::class, 'show']);
  Route::post('orders/{order}/assign', [OrderManagementController::class, 'assignPartner']);
  Route::post('orders/{order}/unassign', [OrderManagementController::class, 'unassignPartner']);
  Route::patch('orders/{order}/status', [OrderManagementController::class, 'updateStatus']);
  Route::post('orders/{order}/cancel', [OrderManagementController::class, 'cancel']);

  // Dashboard & Analytics
  Route::get('stats', [AdminDashboardController::class, 'stats']);
  Route::get('analytics', [AdminDashboardController::class, 'analytics']);
  Route::get('stats/realtime', [AdminDashboardController::class, 'realtimeStats']);
  Route::get('reports/payments', [AdminDashboardController::class, 'paymentReports']);

  // Notifications (Admin sending to users)
  Route::post('notifications/send', [NotificationController::class, 'send']);
  Route::post('notifications/send-bulk', [NotificationController::class, 'sendBulk']);
});

// User routes (require authentication)
Route::middleware(['auth:sanctum'])->group(function () {
  Route::get('profile', [ProfileController::class, 'show']);
  Route::put('profile', [ProfileController::class, 'update']);

  // Cart management
  Route::post('cart', [CartController::class, 'store']);
  Route::get('cart', [CartController::class, 'index']);
  Route::put('cart/{id}', [CartController::class, 'update']);
  Route::delete('cart/{id}', [CartController::class, 'destroy']);

  // Order management
  Route::post('orders', [OrderController::class, 'store']);
  Route::get('orders', [OrderController::class, 'index']);
  Route::get('orders/{id}', [OrderController::class, 'show']);

  // Address management
  Route::get('addresses', [AddressController::class, 'index']);
  Route::post('addresses', [AddressController::class, 'store']);
  Route::get('addresses/default', [AddressController::class, 'getDefault']);
  Route::get('addresses/{id}', [AddressController::class, 'show']);
  Route::put('addresses/{id}', [AddressController::class, 'update']);
  Route::delete('addresses/{id}', [AddressController::class, 'destroy']);
  Route::patch('addresses/{id}/default', [AddressController::class, 'setDefault']);

  // Payment management
  Route::prefix('payments')->group(function () {
    Route::post('create-order', [PaymentController::class, 'createOrder']);
    Route::post('verify', [PaymentController::class, 'verifyPayment']);
    Route::get('{orderId}/status', [PaymentController::class, 'status']);
    Route::get('history', [PaymentController::class, 'history']);
  });

  // Push notification token management
  Route::prefix('notifications')->group(function () {
    Route::post('register-token', [NotificationController::class, 'registerToken']);
    Route::delete('unregister-token', [NotificationController::class, 'unregisterToken']);
    Route::get('history', [NotificationController::class, 'history']);
    Route::get('{id}', [NotificationController::class, 'show']);
  });
});
