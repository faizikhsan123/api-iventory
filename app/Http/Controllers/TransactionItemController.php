<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransaction_itemRequest;
use App\Http\Requests\UpdateTransaction_itemRequest;
use App\Http\Resources\ItemTransactionResource;
use App\Models\Activity;
use App\Models\Item;
use App\Models\StockHistory;
use App\Models\TransactionItem;
use Illuminate\Support\Facades\Auth;

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
            'data' => ItemTransactionResource::collection($transactionItem),
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
        $validated = $request->validated();

        $item = Item::findOrFail($validated['items_id']);

        if ($item->current_stock < $validated['qty']) {
            return response()->json([
                'success' => false,
                'message' => 'Stok tidak mencukupi',
            ], 422);
        }

        $transactionItem = TransactionItem::create([
            'transactions_id' => $validated['transactions_id'],
            'items_id' => $validated['items_id'],
            'qty' => $validated['qty'],
        ]);

        $item->decrement('current_stock', $validated['qty']);

        StockHistory::create([
            'item_id' => $validated['items_id'],
            'qty' => $validated['qty'],
            'type' => 'out',
            'note' => $request->input('note'),
            'user_id' => Auth::id(),
            'date' => $request->input('date') ?? now()->toDateString(),
        ]);

        Activity::create([
            'user_id' => Auth::user()->id,
            'activity' => 'Memberikan Barang',
            'detail' => "Barang {$item['name']} Diberikan Sebanyak {$validated['qty']}",
            'type' => 'stockout',
            'date' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Item berhasil ditambahkan',
            'data' => new ItemTransactionResource($transactionItem->load('item', 'transaction')),
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
            'data' => new ItemTransactionResource($transactionItem),
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
        $request->validated();

        $oldqty = $transactionItem->qty;
        $newQty = $request->qty;

        $item = Item::findOrFail($transactionItem->items_id);

        $diffrence = $newQty - $oldqty;

        if ($diffrence > 0) {
            if ($item->current_stock < $diffrence) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stok tidak mencukupi',
                ], 422);
            }
            $item->update([
                'current_stock' => $item->current_stock - $diffrence,
            ]);
        } else {
            $item->update([
                'current_stock' => $item->current_stock + abs($diffrence),
            ]);
        }

        $transactionItem->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Data TransactionItem Berhasil Diubah',
            'data' => new ItemTransactionResource($transactionItem),
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
