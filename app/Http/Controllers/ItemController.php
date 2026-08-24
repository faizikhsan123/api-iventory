<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Http\Resources\ItemsResourcec;
use App\Models\Activity;
use Illuminate\Support\Facades\Auth;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $Items = Item::latest()->get();
        return response()->json([
            'success' => true,
            'message' => 'Data Itemm Ditemukan',
            'data' => ItemsResourcec::collection($Items)
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
    public function store(StoreItemRequest $request)
    {
        // Generate Part Number
        $partNumber = 'ITM-' . str_pad(
            Item::count() + 1,
            6,
            '0',
            STR_PAD_LEFT
        );

        $filePath = null;

        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('items', 'public');
        }

        $items = Item::create([
            ...$request->validated(),
            'part_number' => $partNumber,
            'file' => $filePath,
            'current_stock' => 0,
            'status' => 'available',
        ]);

        Activity::create([
            'user_id' => Auth::user()->id,
            'activity' => "Add Item {$items['name']}",
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data Item Berhasil Ditambahkan',
            'data' => new ItemsResourcec($items)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Item $item)
    {
        return response()->json([
            'success' => true,
            'message' => 'Data Item Ditemukan',
            'data' => new ItemsResourcec($item)
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Item $item)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateItemRequest $request, Item $item)
    {
        $item->update($request->validated());
        Activity::create([
            'user_id' => Auth::user()->id,
            'activity' => "Update Item {$item['name']}",
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Data Item Berhasil Diubah',
            'data' => new ItemsResourcec($item)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Item $item)
    {
        $item->delete();
         Activity::create([
            'user_id' => Auth::user()->id,
            'activity' => "delete Item {$item['name']}",
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data Item Berhasil Dihapus'
        ]);
    }
}
