    <?php

    use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeCpdController;
use App\Http\Controllers\EmployesController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceDocumentController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\McuController;
use App\Http\Controllers\PerformanceReviewController;
use App\Http\Controllers\StockHistoryController;
use App\Http\Controllers\StockOutController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\TrainingParticipantController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransactionItemController;
use App\Http\Controllers\TurnoverController;
use App\Models\Invoice;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/employes/{employe}/detail', [EmployesController::class, 'detail']);
    Route::get('/employes/{employe}/cpd', [EmployeeCpdController::class, 'show']);
    Route::get('/employes/{employe}/performance-reviews', [PerformanceReviewController::class, 'index']);

    // invoicing (tracking invoice jasa): semua user login boleh lihat, ubah khusus admin
    Route::apiResource('invoices', InvoiceController::class)->only(['index', 'show']);

    Route::get('/invoices/{invoice}/documents/{type}', [InvoiceDocumentController::class, 'show'])
        ->whereIn('type', Invoice::DOCUMENT_TYPES)
        ->middleware('throttle:60,1');

    Route::middleware('role:admin')->group(function () {
        Route::apiResource('invoices', InvoiceController::class)->except(['index', 'show']);
        Route::post('/invoices/{invoice}/status', [InvoiceController::class, 'updateStatus']);
        Route::post('/invoices/{invoice}/documents', [InvoiceDocumentController::class, 'store'])
            ->middleware('throttle:20,1');
        Route::delete('/invoices/{invoice}/documents/{type}', [InvoiceDocumentController::class, 'destroy'])
            ->whereIn('type', Invoice::DOCUMENT_TYPES);
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
        Route::post('/contracts/{employe}/resign', [ContractController::class, 'resign']);
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

    // ---- READ (admin + office) ----
    Route::get('/items/export-low-stock', [ItemController::class, 'exportLowStock']);
    Route::get('/items/export-ranking', [ItemController::class, 'exportRanking']);
    Route::get('/items/low-stock', [ItemController::class, 'lowStock']);
    Route::get('/items/{item}/detail', [ItemController::class, 'detail']);
    Route::get('/items/top-borrowed', [ItemController::class, 'topBorrowed']);
    Route::get('/items/stock-on-hand', [ItemController::class, 'stockOnHand']);
    Route::get('/items/export-stock-on-hand', [ItemController::class, 'exportStockOnHand']);
    Route::get('/transactions/export', [TransactionController::class, 'export']);
    Route::get('/stock-history/export-in', [StockHistoryController::class, 'exportIn']);
    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);
    Route::get('/dashboard/turnover', [TurnoverController::class, 'index']);
    Route::get('trainings/{training}/participants', [TrainingParticipantController::class, 'index']);
    //hbjhbjhbjhbjhbjbhjbjbjb
    Route::get('participants/{participant}/file', [TrainingParticipantController::class, 'showFile']);

    Route::apiResource('suppliers', SupplierController::class)->only(['index', 'show']);
    Route::apiResource('items', ItemController::class)->only(['index', 'show']);
    Route::apiResource('transactions', TransactionController::class)->only(['index', 'show']);
    Route::apiResource('transaction-items', TransactionItemController::class)->only(['index', 'show']);
    Route::apiResource('activities', ActivityController::class)->only(['index', 'show']);
    Route::apiResource('stock-history', StockHistoryController::class)->only(['index', 'show']);
    Route::apiResource('groups', GroupController::class)->only(['index', 'show']);
    Route::apiResource('trainings', TrainingController::class)->only(['index', 'show']);

    // ---- WRITE (khusus admin; office read-only) ----
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('suppliers', SupplierController::class)->except(['index', 'show']);
        Route::apiResource('items', ItemController::class)->except(['index', 'show']);
        Route::apiResource('transactions', TransactionController::class)->except(['index', 'show']);
        Route::apiResource('transaction-items', TransactionItemController::class)->except(['index', 'show']);
        Route::apiResource('activities', ActivityController::class)->except(['index', 'show']);
        Route::apiResource('stock-history', StockHistoryController::class)->except(['index', 'show']);
        Route::apiResource('groups', GroupController::class)->except(['index', 'show']);
        Route::apiResource('trainings', TrainingController::class)->except(['index', 'show']);

        // stok keluar & stok masuk
        Route::post('/stock-out', [StockOutController::class, 'store']);
        Route::post('/stock-history/in', [StockHistoryController::class, 'storeIn']);

        // koreksi qty saja (barang keluar = transaction_item, barang masuk = stock_history type in)
        Route::patch('/transaction-items/{transactionItemId}/quantity', [TransactionItemController::class, 'updateQuantity'])->whereNumber('transactionItemId');
        Route::patch('/stock-history/{stockHistoryId}/quantity', [StockHistoryController::class, 'updateQuantity'])->whereNumber('stockHistoryId');

        // peserta training (upload file)
        Route::post('trainings/{training}/participants', [TrainingParticipantController::class, 'store']);
        Route::post('participants/{participant}', [TrainingParticipantController::class, 'update']);
        Route::delete('participants/{participant}', [TrainingParticipantController::class, 'destroy']);
    });
});
