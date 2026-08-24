<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Activity;
use Illuminate\Support\Facades\Auth;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $supplier = Supplier::latest()->get();
        return response()->json([
            'success' => true,
            'message' => 'Data Supplier Ditemukan',
            'data' => SupplierResource::collection($supplier)
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
    public function store(StoreSupplierRequest $request)
    {
        $supplier = Supplier::create($request->validated());
        
         Activity::create([
            'user_id' => Auth::user()->id,
            'activity' => "Add Supplier {$supplier['name']}",
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Data Supplier Berhasil Ditambahkan',
            'data' => new SupplierResource($supplier)
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier)
    {
        return response()->json([
            'success' => true,
            'message' => 'Data Supplier Ditemukan',
            'data' => new SupplierResource($supplier)
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Supplier $supplier)
    {
        
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        $supplier->update($request->validated());
         Activity::create([
            'user_id' => Auth::user()->id,
            'activity' => "Update Supplier {$supplier['name']}",
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Data Supplier Berhasil Diubah',
            'data' => new SupplierResource($supplier)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
         Activity::create([
            'user_id' => Auth::user()->id,
            'activity' => "Delete Supplier {$supplier['name']}",
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Data Supplier Berhasil Dihapus'
            
        ]);
    }
}
