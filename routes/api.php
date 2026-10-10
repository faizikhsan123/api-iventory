    <?php

    use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeCpdController;
use App\Http\Controllers\EmployesController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\McuController;
use App\Http\Controllers\PerformanceReviewController;
use App\Http\Controllers\RfqController;
use App\Http\Controllers\StockHistoryController;
use App\Http\Controllers\StockOutController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\TrainingParticipantController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransactionItemController;
use App\Http\Controllers\TurnoverController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/logout', [AuthController::class, 'logout']);

    // suppliers
    Route::apiResource('suppliers', SupplierController::class);

    Route::get('/employes/{employe}/detail', [EmployesController::class, 'detail']);
    Route::get('/employes/{employe}/cpd', [EmployeeCpdController::class, 'show']);
    Route::get('/employes/{employe}/performance-reviews', [PerformanceReviewController::class, 'index']);

    // rfq (log permintaan penawaran): semua user login boleh lihat, ubah khusus admin
    Route::get('/rfqs/options', [RfqController::class, 'options']);
    Route::apiResource('rfqs', RfqController::class)->only(['index', 'show']);

    Route::middleware('role:admin')->group(function () {
        Route::apiResource('rfqs', RfqController::class)->except(['index', 'show']);
        Route::post('/rfqs/{rfq}/updates', [RfqController::class, 'addUpdate']);
        Route::delete('/rfq-updates/{rfqUpdate}', [RfqController::class, 'destroyUpdate']);
    });

    // invoicing (tracking invoice jasa): semua user login boleh lihat, ubah khusus admin
    Route::apiResource('invoices', InvoiceController::class)->only(['index', 'show']);

    Route::middleware('role:admin')->group(function () {
        Route::apiResource('invoices', InvoiceController::class)->except(['index', 'show']);
        Route::post('/invoices/{invoice}/status', [InvoiceController::class, 'updateStatus']);
    });

    // mcu: semua user login boleh lihat, tambah/ubah/hapus khusus admin
    Route::apiResource('mcus', McuController::class)->only(['index', 'show']);

    Route::middleware('role:admin')->group(function () {
        Route::apiResource('mcus', McuController::class)->except(['index', 'show']);
    });

    // contract: semua user login boleh lihat, perpanjang/hapus khusus admin
    Route::get('/contracts', [ContractController::class, 'index']);
    Route::get('/contracts/{employe}', [ContractController::class, 'show']);

    Route::middleware('role:admin')->group(function () {
        Route::post('/contracts/{employe}/renewals', [ContractController::class, 'storeRenewal']);
        Route::delete('/contract-renewals/{contractRenewal}', [ContractController::class, 'destroyRenewal']);
        Route::post('/employes/{employe}/performance-reviews', [PerformanceReviewController::class, 'store']);
        Route::put('/employes/{employe}/cpd', [EmployeeCpdController::class, 'upsert']);
        Route::delete('/performance-reviews/{performanceReview}', [PerformanceReviewController::class, 'destroy']);
    });

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

    Route::get('/items/stock-on-hand', [ItemController::class, 'stockOnHand']);

    Route::get('/items/export-stock-on-hand', [ItemController::class, 'exportStockOnHand']);

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
    Route::get('/dashboard/turnover', [TurnoverController::class, 'index']);

    // group
    Route::apiResource('groups', GroupController::class);

    // training
    Route::apiResource('trainings', TrainingController::class);

    Route::get('trainings/{training}/participants', [TrainingParticipantController::class, 'index']);
    Route::post('trainings/{training}/participants', [TrainingParticipantController::class, 'store']);
    Route::post('participants/{participant}', [TrainingParticipantController::class, 'update']);
    Route::get('participants/{participant}/file', [TrainingParticipantController::class, 'showFile']);
    Route::delete('participants/{participant}', [TrainingParticipantController::class, 'destroy']);
});
