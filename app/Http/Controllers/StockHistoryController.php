<?php

namespace App\Http\Controllers;

use App\Models\StockHistory;
use App\Http\Requests\StoreStockHistoryRequest;
use App\Http\Requests\UpdateStockHistoryRequest;
use App\Http\Resources\StockHistoryResource;
use App\Models\Item;
use Illuminate\Support\Facades\Auth;

class StockHistoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $stockHistory = StockHistory::with('user', 'item', 'supplier', 'transaction')->latest()->get();
        return response()->json([
            'success' => true,
            'message' => 'Data StockHistory Ditemukan',
            'data' => StockHistoryResource::collection($stockHistory)
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
    public function store(StoreStockHistoryRequest $request)
    {
         
        $item = Item::findOrFail($request->item_id);
        $item->update([
            'current_stock' => $item->current_stock + $request->qty
        ]);
        $stockHistory = StockHistory::create([
            ...$request->validated(),
            'type' => 'in',
            'note' => 'Stock Masuk',
            'user_id' => Auth::id()          
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data StockHistory Berhasil Ditambahkan',
            'data' => new StockHistoryResource($stockHistory)
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
            'data' => new StockHistoryResource($stockHistory)
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
