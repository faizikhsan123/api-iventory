<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Http\Resources\TransactionResource;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $Transaksii = Transaction::with('employes')->latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'Data Transaksi Ditemukan',
            'data' => TransactionResource::collection($Transaksii)
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTransactionRequest $request)
    {
        $transactionNumber = 'TRX-' . str_pad(
            Transaction::count() + 1,
            5,
            '0',
            STR_PAD_LEFT
        );

        $transaksi = Transaction::create([
            'transaction_number' => $transactionNumber,
            'employes_id' => $request->employes->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data Transaksi Berhasil Ditambahkan',
            'data' => new TransactionResource($transaksi)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaction $transaction)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Transaction $transaction) {}

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTransactionRequest $request, Transaction $transaction)
    {
        $transaction->update($request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Data Transaksi Berhasil Diubah',
            'data' => new TransactionResource($transaction)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction)
    {
        $transaction->delete();
        return response()->json([
            'success' => true,
            'message' => 'Data Transaksi Berhasil Dihapus'
        ]);
    }
}
