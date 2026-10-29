<?php

namespace App\Services;

use App\Models\Item;
use App\Models\StockHistory;
use App\Models\TransactionItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Koreksi qty barang keluar (transaction_items) / barang masuk (stock_histories type=in).
 * Semua dalam satu transaksi, baris + item di-lock. Harga (price/avg_price/unit_price) TIDAK diubah.
 */
class StockQuantityAdjuster
{
    /**
     * @return array{changed: bool, old_qty: int, new_qty: int, delta: int, item: Item}|null null = stok tidak cukup/jadi negatif
     */
    public function adjustStockOut(int $transactionItemId, int $newQty): ?array
    {
        return DB::transaction(function () use ($transactionItemId, $newQty) {
            $row = TransactionItem::lockForUpdate()->with('transaction')->findOrFail($transactionItemId);
            $item = Item::lockForUpdate()->findOrFail($row->items_id);
            $old = (int) $row->qty;
            $delta = $newQty - $old;

            if ($delta === 0) {
                return ['changed' => false, 'old_qty' => $old, 'new_qty' => $newQty, 'delta' => 0, 'item' => $item];
            }

            // qty naik -> stok berkurang sebesar delta; qty turun -> stok bertambah
            $newStock = (int) $item->current_stock - $delta;
            if ($newStock < 0) {
                return null;
            }

            // ledger: baris history 'out' kembaran (dibuat bersamaan, tanpa FK) di-update; kalau tak ditemukan, catat koreksi bertanda
            $ledger = StockHistory::lockForUpdate()
                ->where('item_id', $row->items_id)
                ->where('type', 'out')
                ->where('qty', $old)
                ->whereBetween('created_at', [$row->created_at->copy()->subSeconds(10), $row->created_at->copy()->addSeconds(10)])
                ->orderByRaw('ABS(TIMESTAMPDIFF(SECOND, created_at, ?))', [$row->created_at->toDateTimeString()])
                ->first();

            if ($ledger) {
                $ledger->update(['qty' => $newQty]);
            } else {
                StockHistory::create([
                    'item_id' => $row->items_id,
                    'qty' => $delta, // bertanda: kolom qty integer biasa, SUM(out) tetap benar
                    'type' => 'out',
                    'note' => "Koreksi qty barang keluar #{$row->id}: {$old} -> {$newQty}",
                    'user_id' => Auth::id(),
                    'date' => now()->toDateString(),
                ]);
            }

            $row->update(['qty' => $newQty]);
            $item->update(['current_stock' => $newStock]);

            return ['changed' => true, 'old_qty' => $old, 'new_qty' => $newQty, 'delta' => $delta, 'item' => $item];
        });
    }

    /**
     * @return array{changed: bool, old_qty: int, new_qty: int, delta: int, item: Item}|null null = stok jadi negatif
     */
    public function adjustStockIn(int $stockHistoryId, int $newQty): ?array
    {
        return DB::transaction(function () use ($stockHistoryId, $newQty) {
            $row = StockHistory::lockForUpdate()->where('type', 'in')->findOrFail($stockHistoryId);
            $item = Item::lockForUpdate()->findOrFail($row->item_id);
            $old = (int) $row->qty;
            $delta = $newQty - $old;

            if ($delta === 0) {
                return ['changed' => false, 'old_qty' => $old, 'new_qty' => $newQty, 'delta' => 0, 'item' => $item];
            }

            $newStock = (int) $item->current_stock + $delta;
            if ($newStock < 0) {
                return null;
            }

            $row->update(['qty' => $newQty]);
            $item->update(['current_stock' => $newStock]);

            return ['changed' => true, 'old_qty' => $old, 'new_qty' => $newQty, 'delta' => $delta, 'item' => $item];
        });
    }
}
