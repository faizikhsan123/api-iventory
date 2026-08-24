<?php

namespace App\Http\Controllers;

use App\Models\StockHistory;
use App\Http\Requests\StoreStockHistoryRequest;
use App\Http\Requests\UpdateStockHistoryRequest;
use App\Http\Resources\StockHistoryResource;

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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(StockHistory $stockHistory)
    {
        //
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
