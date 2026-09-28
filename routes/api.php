    <?php

    use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployesController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\StockHistoryController;
use App\Http\Controllers\StockOutController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransactionItemController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/logout', [AuthController::class, 'logout']);

    // suppliers
    Route::apiResource('suppliers', SupplierController::class);

    Route::get('/employes/{employe}/detail', [EmployesController::class, 'detail']);

    
    // employees
    // semua user login boleh lihat
    Route::apiResource('employes', EmployesController::class)
        ->only(['index', 'show']);

    // sisanya (store, update, destroy) khusus admin
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('employes', EmployesController::class)
            ->except(['index', 'show']);
    });



    Route::get('/items/export-low-stock', [ItemController::class, 'exportLowStock']);

    Route::get('/items/export-ranking', [ItemController::class, 'exportRanking']);

    Route::get('/items/low-stock', [ItemController::class, 'lowStock']);

    Route::get('/items/{item}/detail', [ItemController::class, 'detail']);

    Route::get('/items/top-borrowed', [ItemController::class, 'topBorrowed']);

    // items
    Route::apiResource('items', ItemController::class);

    Route::get('/transactions/export', [TransactionController::class, 'export']);

    // transactions
    Route::apiResource('transactions', TransactionController::class);

    // transaction items
    Route::apiResource('transaction-items', TransactionItemController::class);

    // Activity
    Route::apiResource('activities', ActivityController::class);

    // Route::get('/stock-history/trend', [StockHistoryController::class, 'trend']);

    // routes/api.php, taruh SEBELUM apiResource('stock-history', ...)
    Route::get('/stock-history/export-in', [StockHistoryController::class, 'exportIn']);

    // stock history
    Route::apiResource('stock-history', StockHistoryController::class);

    // routes/api.php
    Route::post('/stock-out', [StockOutController::class, 'store']);

    Route::post('/stock-history/in', [StockHistoryController::class, 'storeIn']);

    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);

});
