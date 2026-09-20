<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockOutRequest;
use App\Models\Activity;
use App\Models\Item;
use App\Models\StockHistory;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockOutController extends Controller
{
    public function store(StoreStockOutRequest $request)
    {
        $validated = $request->validated();

        // cek stock semua item dulu sebelum insert apapun
        foreach ($validated['items'] as $itemData) {
            $item = Item::findOrFail($itemData['items_id']);
            if ($item->current_stock < $itemData['qty']) {
                return response()->json([
                    'success' => false,
                    'message' => "Stok {$item->name} tidak mencukupi",
                ], 422);
            }
        }

        $transaction = DB::transaction(function () use ($validated) {
            $transaction = Transaction::create([
                'transaction_number' => 'TRX-'.strtoupper(uniqid()),
                'date' => $validated['date'],
                'employes_id' => $validated['employes_id'],
                'note' => $validated['note'] ?? null,
            ]);

            foreach ($validated['items'] as $itemData) {
                $item = Item::findOrFail($itemData['items_id']);

                TransactionItem::create([
                    'transactions_id' => $transaction->id,
                    'items_id' => $itemData['items_id'],
                    'qty' => $itemData['qty'],
                ]);

                $item->decrement('current_stock', $itemData['qty']);

                StockHistory::create([
                    'item_id' => $itemData['items_id'],
                    'qty' => $itemData['qty'],
                    'type' => 'out',
                    'note' => $validated['note'] ?? null,
                    'user_id' => Auth::id(),
                    'date' => $validated['date'],
                ]);

             
            }

            return $transaction;
        });

        $transaction->load('transaction_items.item', 'employes.user');

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil dibuat',
            'data' => $transaction,
        ]);
    }
}
