<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Http\Resources\ItemsResourcec;
use App\Models\Activity;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ItemController extends Controller
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

        $query = Item::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->input('search').'%');

        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));

        }
        $Items = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Data Itemm Ditemukan',
            'data' => ItemsResourcec::collection($Items),
            'meta' => [
                'current_page' => $Items->currentPage(),
                'last_page' => $Items->lastPage(),
                'per_page' => $Items->perPage(),
                'total' => $Items->total(),
            ],
        ]);
    }



    public function lowStock(Request $request)
    {
        // where raw ini ngambil dari db
        $items = Item::whereRaw('current_stock < min_stock')
            ->latest()
            ->paginate($request->per_page ?? 10);

        return response()->json([
            'success' => true,
            'message' => 'Data Item Ditemukan',
            'data' => ItemsResourcec::collection($items),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
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
    public function store(StoreItemRequest $request)
    {
        // Generate Part Number
        $partNumber = 'ITM-'.str_pad(
            Item::count() + 1,
            6,
            '0',
            STR_PAD_LEFT
        );

        $filePath = null;

        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('items', 'public');
        }

        $data = $request->validated();

        $items = Item::create([

            'name' => $data['name'],
            'category' => $data['category'],
            'brand' => $data['brand'],
            'type' => $data['type'] ?? null,
            'min_stock' => $data['min_stock'] ?? null,
            'size' => $data['size'],
            'unit' => $data['unit'],
            'description' => $data['description'] ?? null,
            'part_number' => $partNumber,
            'file' => $filePath,
            'current_stock' => 0,
            'status' => 'out_of_stock',
        ]);

        Activity::create([
            'user_id' => Auth::user()->id,
            'activity' => 'Menambah Barang',
            'detail' => "Barang {$data['name']} berhasil Ditambah",
            'type' => null,
            'date' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data Item Berhasil Ditambahkan',
            'data' => new ItemsResourcec($items),
        ], 201);
    }

    public function topBorrowed(Request $request)
    {
        // diinpput berdasarkan date  defaultt bulan saa inni
        $start = $request->input('start', now()->startOfMonth());
        $end = $request->input('end', now()->endOfMonth());

        $data = Item::query()
        // buat field total_pinjam dari query berikut
            ->withSum(['stock_history as total_pinjam' => function ($q) use ($start, $end) {
                $q->where('type', 'out')->whereBetween('date', [$start, $end]);
            }], 'qty')
            // having ini utnuk menyimpan nilai total_pinjam yg lebih dari 0
            ->having('total_pinjam', '>', 0)
            // diurutkann
            ->orderByDesc('total_pinjam')
            ->limit(10)
            ->get()
            ->values()
            // ketika dpt nilainya maka  di map semua item dan dimasukkan ke get topBorrowed
            ->map(function ($item, $i) {
                // total ppinjam dimabil dari  yg diats
                // ranknya sesuaikan inndex + 1
                $item->total_pinjam = $item->total_pinjam ?? 0;
                $item->rank = $i + 1;

                return $item;
            });

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Item $item)
    {
        return response()->json([
            'success' => true,
            'message' => 'Data Item Ditemukan',
            'data' => new ItemsResourcec($item),
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
        $filePath = $item->file;

        if ($request->hasFile('file')) {

            $filePath = $request->file('file')->store('items', 'public');
        }

        $data = $request->validated();

        $item->update([
            'name' => $data['name'],
            'category' => $data['category'],
            'brand' => $data['brand'],
            'type' => $data['type'] ?? null,
            'min_stock' => $data['min_stock'] ?? null,
            'size' => $data['size'],
            'unit' => $data['unit'],
            'description' => $data['description'] ?? null,
            'file' => $filePath,
        ]);

        Activity::create([
            'user_id' => Auth::user()->id,
            'activity' => 'Merubah Data Barang',
            'detail' => "Data Barang {$data['name']} Berhasil Dirubah",
            'type' => null,
            'date' => now(),

        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data Item Berhasil Diubah',
            'data' => new ItemsResourcec($item->fresh()), // fresh() biar ambil data terbaru
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
            'activity' => 'Menghapus Barang',
            'detail' => "Barang {$item['name']} berhasil Dihapus",
            'type' => null,
            'date' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data Item Berhasil Dihapus',
        ]);
    }
}
