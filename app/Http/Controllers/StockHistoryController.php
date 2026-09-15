<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransaction_itemRequest;
use App\Http\Requests\UpdateStockHistoryRequest;
use App\Http\Resources\ItemTransactionResource;
use App\Http\Resources\StockHistoryResource;
use App\Models\Item;
use App\Models\StockHistory;
use App\Models\TransactionItem;
use Illuminate\Support\Facades\Auth;

class StockHistoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $stockHistory = StockHistory::with([
            'user',
            'item',
            'supplier' => function ($query) {
                $query->where('status', 'active');
            },
        ])->latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'Data StockHistory Ditemukan',
            'data' => StockHistoryResource::collection($stockHistory),
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
        $request->validated();

        // ambil item
        $item = Item::findOrFail($request->items_id);

        // jika item stock tidak mencukupi dari request qty maka tampilkan error
        if ($item->current_stock < $request->qty) {
            return response()->json([
                'success' => false,
                'message' => 'Stok tidak mencukupi',
            ]);
        }

        // buat stock history
        StockHistory::create([
            'item_id' => $request->items_id,   // <-- diganti dari 'items_id' jadi 'item_id'
            'qty' => $request->qty,
            'type' => 'out',
            'note' => 'Stock Keluar',
            'user_id' => Auth::id(),
        ]);

        // jika ada kurangi
        $item->update([
            'current_stock' => $item->current_stock - $request->qty,
        ]);

        $transactionItem = TransactionItem::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Data TransactionItem Ditemukan',
            'data' => new ItemTransactionResource($transactionItem),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(StockHistory $stockHistory)
    {
        $stockHistory->load('user', 'item', 'supplier', 'transaction');

        return response()->json([
            'success' => true,
            'message' => 'Data StockHistory Ditemukan',
            'data' => new StockHistoryResource($stockHistory),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(StockHistory $stockHistory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStockHistoryRequest $request, StockHistory $stockHistory)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StockHistory $stockHistory)
    {
        //
    }
}
