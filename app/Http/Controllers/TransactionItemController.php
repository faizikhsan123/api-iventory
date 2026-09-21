<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransaction_itemRequest;
use App\Http\Requests\UpdateTransaction_itemRequest;
use App\Http\Resources\ItemTransactionResource;
use App\Models\Activity;
use App\Models\Item;
use App\Models\StockHistory;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
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
            'data' => ItemTransactionResource::collection($transactionItem),
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
        $validated = $request->validated();

        $item = Item::findOrFail($validated['items_id']);

        if ($item->current_stock < $validated['qty']) {
            return response()->json([
                'success' => false,
                'message' => 'Stok tidak mencukupi',
            ], 422);
        }

        $transactionItem = TransactionItem::create([
            'transactions_id' => $validated['transactions_id'],
            'items_id' => $validated['items_id'],
            'qty' => $validated['qty'],
        ]);

        $item->decrement('current_stock', $validated['qty']);

        StockHistory::create([
            'item_id' => $validated['items_id'],
            'qty' => $validated['qty'],
            'type' => 'out',
            'note' => $request->input('note'),
            'user_id' => Auth::id(),
            'date' => $request->input('date') ?? now()->toDateString(),
        ]);

        Activity::create([
            'user_id' => Auth::user()->id,
            'activity' => 'Memberikan Barang',
            'detail' => "Barang {$item['name']} Diberikan Sebanyak {$validated['qty']}",
            'type' => 'stockout',
            'date' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Item berhasil ditambahkan',
            'data' => new ItemTransactionResource($transactionItem->load('item', 'transaction')),
        ]);
    }

    // public function topBorrowed(Request $request)
    // {
    //     // ambil parameter 'start' dari query string, kalau gak ada default ke awal bulan ini
    //     $start = $request->input('start', now()->startOfMonth());
    //     // ambil parameter 'end' dari query string, kalau gak ada default ke akhir bulan ini
    //     $end = $request->input('end', now()->endOfMonth());

    //     // mulai query builder dari model Item
    //     $data = Item::query()
    //         // eager-load relasi stockHistories, tapi bukan datanya — cuma SUM(qty)-nya
    //         // hasilnya jadi kolom virtual "total_pinjam" di tiap Item
    //         ->withSum(['stockHistories as total_pinjam' => function ($q) use ($start, $end) {
    //             // filter cuma yang type-nya 'out' (barang keluar/dipinjam)
    //             $q->where('type', 'out')
    //                 // dan tanggalnya di antara start-end (periode yang diminta)
    //                 ->whereBetween('date', [$start, $end]);
    //         }], 'qty') // <- ini kolom yang di-SUM

    //         // buang item yang total_pinjam-nya 0 atau null (gak pernah keluar di periode ini)
    //         //having dipakai (bukan where) karena total_pinjam itu hasil agregasi (SUM), dan di SQL, filter atas hasil agregasi wajib pakai HAVING, bukan WHERE
    //         ->having('total_pinjam', '>', 0)

    //         // urutkan dari yang paling sering dipinjam ke paling jarang
    //         ->orderByDesc('total_pinjam')

    //         // ambil 10 teratas aja
    //         ->limit(10)

    //         // eksekusi query, hasilnya Collection of Item
    //         ->get()

    //         // reset index array collection jadi 0,1,2,... (biar rank di bawah bener urutannya)
    //         ->values()

    //         // loop tiap item buat nambahin field tambahan
    //         ->map(function ($item, $i) {
    //             // withSum bisa hasilin null kalau gak ada history sama sekali, jadi di-fallback ke 0
    //             $item->total_pinjam = $item->total_pinjam ?? 0;
    //             // rank = posisi di array + 1 (karena array mulai dari 0)
    //             $item->rank = $i + 1;

    //             return $item;
    //         });

    //     // balikin response JSON standar
    //     return response()->json(['success' => true, 'data' => $data]);
    // }

    /**
     * Display the specified resource.
     */
    public function show(TransactionItem $transactionItem)
    {
        $transactionItem->load('transaction', 'item');

        return response()->json([
            'success' => true,
            'message' => 'Data TransactionItem Ditemukan',
            'data' => new ItemTransactionResource($transactionItem),
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
                'current_stock' => $item->current_stock - $diffrence,
            ]);
        } else {
            $item->update([
                'current_stock' => $item->current_stock + abs($diffrence),
            ]);
        }

        $transactionItem->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Data TransactionItem Berhasil Diubah',
            'data' => new ItemTransactionResource($transactionItem),
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
