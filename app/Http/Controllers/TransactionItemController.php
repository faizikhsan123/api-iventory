<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransaction_itemRequest;
use App\Models\TransactionItem;
use App\Http\Requests\UpdateTransaction_itemRequest;
use App\Http\Resources\ItemTransactionResource;
use App\Models\Item;
use App\Models\StockHistory;
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
            'items_id' => $request->items_id,
            'qty' => $request->qty,
            'type' => 'out',
            'note' => 'Stock Keluar',
            'user_id' => Auth::id()
        ]);

        // jika ada kurangi
        $item->update([
            'current_stock' => $item->current_stock - $request->qty
        ]);
        

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
                'current_stock' => $item->current_stock - $diffrence   
            ]);
        } else {
            $item->update([
                'current_stock' => $item->current_stock + abs($diffrence)
            ]);
        }

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
