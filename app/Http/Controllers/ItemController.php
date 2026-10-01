<?php

namespace App\Http\Controllers;

use App\Exports\recordPengeluaran;
use App\Exports\StockOnHandExport;
use App\Exports\StokKritisExport;
use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Http\Resources\ItemsResourcec;
use App\Models\Activity;
use App\Models\Item;
use App\Models\ItemPriceHistory;
use App\Models\StockHistory;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ItemController extends Controller
{
    public function exportRanking(Request $request)
    {
        $start = $request->input('start', now()->startOfMonth()->toDateString());
        $end = $request->input('end', now()->endOfMonth()->toDateString());

        return Excel::download(
            new recordPengeluaran($start, $end),
            "ranking-pengeluaran-{$start}-sd-{$end}.xlsx"
        );
    }

    public function exportLowStock()
    {
        return Excel::download(
            new StokKritisExport,
            'stok-kritis-'.now()->format('Y-m-d_His').'.xlsx'
        );
    }

    public function exportStockOnHand(Request $request)
{
    return Excel::download(
        new StockOnHandExport($request->input('search'), $request->input('category')),
        'stock-on-hand-'.now()->format('Y-m-d_His').'.xlsx'
    );
}

    public function index(Request $request)
    {
        $perPage = max(1, min($request->integer('per_page', 10), 100));

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

    public function stockOnHand(Request $request)
    {
        $perPage = max(1, min($request->integer('per_page', 10), 100));

        $query = Item::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->input('search').'%');
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        $totalNilai = (clone $query)->sum(DB::raw('COALESCE(current_stock, 0) * avg_price'));

        $items = $query->orderBy('name')->paginate($perPage);

        $rows = $items->getCollection()->map(fn ($item) => [
            'id' => $item->id,
            'name' => $item->name,
            'category' => $item->category,
            'unit' => $item->unit,
            'current_stock' => (int) $item->current_stock,
            'stock_value' => round((int) $item->current_stock * (float) $item->avg_price, 2),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data Stock On Hand Ditemukan',
            'data' => $rows,
            'total_nilai' => round((float) $totalNilai, 2),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function lowStock(Request $request)
    {
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

    public function detail(Item $item)
    {
        $riwayatStok = StockHistory::where('item_id', $item->id)
            ->with(['user', 'supplier'])
            ->latest()
            ->limit(20)
            ->get()
            ->map(function ($history) {
                return [
                    'date' => $history->date,
                    'type' => $history->type,
                    'qty' => $history->qty,
                    'note' => $history->note,
                    'user_name' => $history->user->name ?? '-',
                    'supplier_name' => $history->supplier->name ?? null,
                ];
            });

        $riwayatPemberian = TransactionItem::where('items_id', $item->id)
            ->with(['transaction.employes.user'])
            ->latest()
            ->limit(20)
            ->get()
            ->map(function ($transactionItem) {
                $transaction = $transactionItem->transaction;

                return [
                    'transaction_number' => $transaction->transaction_number ?? '-',
                    'date' => $transaction->date ?? '-',
                    'qty' => $transactionItem->qty,
                    'employe_name' => $transaction->employes->user->name ?? '-',
                    'note' => $transaction->note,
                ];
            });

        $riwayatHarga = ItemPriceHistory::where('item_id', $item->id)
            ->with('user')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($h) => [
                'date' => $h->created_at->toDateString(),
                'old_price' => (float) $h->old_price,
                'new_price' => (float) $h->new_price,
                'user_name' => $h->user->name ?? '-',
            ]);

        $totalDiberikan = TransactionItem::where('items_id', $item->id)->sum('qty');

        $terakhirMasuk = StockHistory::where('item_id', $item->id)
            ->where('type', 'in')
            ->latest('date')
            ->value('date');

        return response()->json([
            'success' => true,
            'message' => 'Data Detail Barang Ditemukan',
            'data' => [
                'item' => new ItemsResourcec($item),
                'statistik' => [
                    'total_diberikan' => (int) $totalDiberikan,
                    'tanggal_terakhir_masuk' => $terakhirMasuk,
                ],
                'riwayat_stok' => $riwayatStok,
                'riwayat_pemberian' => $riwayatPemberian,
                'riwayat_harga' => $riwayatHarga,
            ],
        ]);
    }

    public function store(StoreItemRequest $request)
    {
        $filePath = null;

        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('items', 'public');
        }

        $data = $request->validated();
        $price = (float) ($data['price'] ?? 0);

        $items = Item::create([
            'name' => $data['name'],
            'category' => $data['category'],
            'brand' => $data['brand'] ?? null,
            'type' => $data['type'] ?? null,
            'min_stock' => $data['min_stock'] ?? null,
            'unit' => $data['unit'],
            'price' => $price,
            'avg_price' => $price,
            'description' => $data['description'] ?? null,
            'part_number' => $data['part_number'] ?? null,
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
        $start = $request->input('start', now()->startOfMonth());
        $end = $request->input('end', now()->endOfMonth());

        $data = Item::query()
            ->withSum(['stock_history as total_pinjam' => function ($q) use ($start, $end) {
                $q->where('type', 'out')->whereBetween('date', [$start, $end]);
            }], 'qty')
            ->having('total_pinjam', '>', 0)
            ->orderByDesc('total_pinjam')
            ->limit(10)
            ->get()
            ->values()
            ->map(function ($item, $i) {
                $item->total_pinjam = $item->total_pinjam ?? 0;
                $item->rank = $i + 1;

                return $item;
            });

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function show(Item $item)
    {
        return response()->json([
            'success' => true,
            'message' => 'Data Item Ditemukan',
            'data' => new ItemsResourcec($item),
        ]);
    }

    public function update(UpdateItemRequest $request, Item $item)
    {
        $filePath = $item->file;

        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('items', 'public');
        }

        $data = $request->validated();

        $oldPrice = (float) $item->price;
        $newPrice = (float) ($data['price'] ?? 0);

        DB::transaction(function () use ($item, $data, $filePath, $oldPrice, $newPrice) {
            $item->update([
                'name' => $data['name'],
                'category' => $data['category'] ?? null,
                'brand' => $data['brand'] ?? null,
                'type' => $data['type'] ?? null,
                'min_stock' => $data['min_stock'] ?? null,
                'part_number' => $data['part_number'] ?? null,
                'unit' => $data['unit'],
                'price' => $newPrice,
                'description' => $data['description'] ?? null,
                'file' => $filePath,
            ]);

            if ($oldPrice !== $newPrice) {
                ItemPriceHistory::create([
                    'item_id' => $item->id,
                    'user_id' => Auth::id(),
                    'old_price' => $oldPrice,
                    'new_price' => $newPrice,
                ]);
            }

            Activity::create([
                'user_id' => Auth::id(),
                'activity' => 'Merubah Data Barang',
                'detail' => "Data Barang {$data['name']} Berhasil Dirubah",
                'type' => null,
                'date' => now(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Data Item Berhasil Diubah',
            'data' => new ItemsResourcec($item->fresh()),
        ]);
    }

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