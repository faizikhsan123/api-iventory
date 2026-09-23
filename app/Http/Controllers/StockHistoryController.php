<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockInRequest;
use App\Http\Requests\StoreTransaction_itemRequest;
use App\Http\Requests\UpdateStockHistoryRequest;
use App\Http\Resources\ItemTransactionResource;
use App\Http\Resources\StockHistoryResource;
use App\Models\Activity;
use App\Models\Item;
use App\Models\StockHistory;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockHistoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = max(1, min($request->integer('per_page', 10), 100));

        $query = StockHistory::with(['item', 'supplier', 'user']);

        if ($request->filled('start')) {
            $query->whereDate('date', '>=', $request->input('start'));
        }

        if ($request->filled('end')) {
            $query->whereDate('date', '<=', $request->input('end'));
        }

        $Stockhistory = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Data StockHistory Ditemukan',
            'data' => StockHistoryResource::collection($Stockhistory),
            'meta' => [
                'current_page' => $Stockhistory->currentPage(),
                'last_page' => $Stockhistory->lastPage(),
                'per_page' => $Stockhistory->perPage(),
                'total' => $Stockhistory->total(),
            ],
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
     * Store a newly created resource in storage. (Stock OUT — dipakai transaksi/karyawan)
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
     * Store a newly created resource in storage. (Stock IN — penerimaan dari supplier, banyak item sekaligus)
     */
    public function storeIn(StoreStockInRequest $request)
    {
        $validated = $request->validated();

        $createdHistories = DB::transaction(function () use ($validated) {
            $histories = [];

            foreach ($validated['items'] as $itemLine) {
                $item = Item::findOrFail($itemLine['item_id']);

                $stockHistory = StockHistory::create([
                    'item_id' => $itemLine['item_id'],
                    'supplier_id' => $validated['supplier_id'],
                    'qty' => $itemLine['qty'],
                    'type' => 'in',
                    'note' => $validated['note'] ?? 'Stock Masuk',
                    'date' => $validated['date'],
                    'user_id' => Auth::id(),
                ]);

                $item->update([
                    'current_stock' => $item->current_stock + $itemLine['qty'],
                ]);

                Activity::create([
                    'user_id' => Auth::id(),
                    'activity' => 'Menambah Stok Barang',
                    'detail' => "Stok Barang {$item->name} bertambah {$itemLine['qty']} {$itemLine['unit']}",
                    'type' => 'stockin',
                    'date' => now(),
                ]);

                $histories[] = $stockHistory->load('user', 'item', 'supplier');
            }

            return $histories;
        });

        return response()->json([
            'success' => true,
            'message' => 'Stock berhasil ditambahkan',
            'data' => StockHistoryResource::collection(collect($createdHistories)),
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
