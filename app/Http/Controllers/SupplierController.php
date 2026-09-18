<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Activity;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = max(1, min(
            $request->integer('per_page', 10),
            100
        ));

        $query = Supplier::query();

        // filter search nama
        if ($request->filled('search')) {
            $query->where(
                'name',
                'like',
                '%'.$request->input('search').'%'
            );
        }

        // filter status
        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->input('status')
            );
        }

        $supplier = $query
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([

            'success' => true,

            'message' => 'Data Supplier Ditemukan',

            'data' => SupplierResource::collection($supplier),

            'meta' => [
                'current_page' => $supplier->currentPage(),
                'last_page' => $supplier->lastPage(),
                'per_page' => $supplier->perPage(),
                'total' => $supplier->total(),
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
     * Store a newly created resource in storage.
     */
    public function store(StoreSupplierRequest $request)
    {
        $data = $request->validated();

        $supplier = Supplier::create([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'status' => 'active',
        ]);

        Activity::create([
            'user_id' => Auth::user()->id,
            'activity' => "Add Supplier {$supplier->name}",
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data Supplier Berhasil Ditambahkan',
            'data' => new SupplierResource($supplier),
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
            'data' => new SupplierResource($supplier),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Supplier $supplier) {}

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        $data = $request->validated();

        $supplier->update([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'status' => $data['status'],
        ]);

        Activity::create([
            'user_id' => Auth::user()->id,
            'activity' => "Update Supplier {$supplier->name}",
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data Supplier Berhasil Diubah',
            'data' => new SupplierResource($supplier),
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
            'activity' => "Delete Supplier {$supplier->name}",
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data Supplier Berhasil Dihapus',

        ]);
    }
}
