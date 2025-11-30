<?php

use Illuminate\Support\Facades\Route;

// Version 1 APIs
Route::prefix('v1')->group(function () {
  require __DIR__ . '/v1.php';
});
