<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['employes.user', 'transaction_items.item']);

        if ($request->filled('start')) {
            $query->whereDate('date', '>=', $request->input('start'));
        }

        if ($request->filled('end')) {
            $query->whereDate('date', '<=', $request->input('end'));
        }

        $transactions = $query->latest()->paginate($request->per_page ?? 10);

        return response()->json([
            'success' => true,
            'message' => 'Data Transaksi Ditemukan',
            'data' => TransactionResource::collection($transactions),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    public function create()
    {
        //
    }

    public function store(StoreTransactionRequest $request)
    {
        $transaction = Transaction::create([
            'transaction_number' => 'TRX-'.strtoupper(uniqid()),
            'date' => $request->date,
            'employes_id' => $request->employes_id,
            'note' => $request->note,
        ]);

        $transaction->load(['employes.user', 'transaction_items.item']);

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil dibuat',
            'data' => new TransactionResource($transaction),
        ]);
    }

    public function show(Transaction $transaction)
    {
        $transaction->load(['employes.user', 'transaction_items.item']);

        return response()->json([
            'success' => true,
            'message' => 'Data Transaksi Ditemukan',
            'data' => new TransactionResource($transaction),
        ]);
    }

    public function edit(Transaction $transaction) {}

    public function update(UpdateTransactionRequest $request, Transaction $transaction)
    {
        $transaction->update($request->validated());
        $transaction->load(['employes.user', 'transaction_items.item']);

        return response()->json([
            'success' => true,
            'message' => 'Data Transaksi Berhasil Diubah',
            'data' => new TransactionResource($transaction),
        ]);
    }

    public function destroy(Transaction $transaction)
    {
        $transaction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data Transaksi Berhasil Dihapus',
        ]);
    }
}