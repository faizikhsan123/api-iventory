<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransaction_itemRequest;
use App\Models\TransactionItem;
use App\Http\Requests\UpdateTransaction_itemRequest;
use App\Http\Resources\ItemTransactionResource;

class TransactionItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $transactionItem = TransactionItem::with('transaction', 'item')->latest()->get();
        return response()->json([
            'success' => true,
            'message' => 'Data TransactionItem Ditemukan',
            'data' => ItemTransactionResource::collection($transactionItem)
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
    public function store(StoreTransaction_itemRequest $request)
    {
        $transactionItem = TransactionItem::create($request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Data TransactionItem Ditemukan',
            'data' => new ItemTransactionResource($transactionItem)
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(TransactionItem $transactionItem)
    {
        $transactionItem->load('transaction', 'item');
        return response()->json([
            'success' => true,
            'message' => 'Data TransactionItem Ditemukan',
            'data' => new ItemTransactionResource($transactionItem)
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TransactionItem $transactionItem)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateTransaction_itemRequest $request,
        TransactionItem $transactionItem
    ) {
        $transactionItem->update($request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Data TransactionItem Berhasil Diubah',
            'data' => new ItemTransactionResource($transactionItem)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TransactionItem $transactionItem)
    {
        $transactionItem->delete();
        return response()->json([
            'success' => true,
            'message' => 'Data TransactionItem Berhasil Dihapus',
        ]);
    }
}
