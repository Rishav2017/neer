<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\OrderController;

// Public routes
Route::prefix('auth')->group(function () {
  Route::post('login', [AuthController::class, 'login']);
});

// Public category and product listing
Route::get('categories', [CategoryController::class, 'index']);
Route::get('products', [ProductController::class, 'index']);

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
});
