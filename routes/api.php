<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\EmployesController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TransactionController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/logout', [AuthController::class, 'logout']);

    // supplier
    Route::apiResource('suppliers', SupplierController::class);

    // employees
    Route::apiResource('employes', EmployesController::class);

    // items
    Route::apiResource('items', ItemController::class);

    // trransaksii
    Route::apiResource('transactions', TransactionController::class);
  
});
