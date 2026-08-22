<?php

use App\Http\Controllers\ActivityController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\EmployesController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\StockHistoryController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransactionItemController;
use App\Models\Activity;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/logout', [AuthController::class, 'logout']);

    // suppliers
    Route::apiResource('suppliers', SupplierController::class);

    // employees
    Route::apiResource('employes', EmployesController::class);

    // items
    Route::apiResource('items', ItemController::class);

    // transactions
    Route::apiResource('transactions', TransactionController::class);

    // transaction items
    Route::apiResource('transaction-items', TransactionItemController::class);

    //Activity
    Route::apiResource('activities', ActivityController::class);

    // stock history
    Route::apiResource('stock-history', StockHistoryController::class);
});