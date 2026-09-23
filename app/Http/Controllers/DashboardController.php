<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\StockHistory;
use App\Models\Transaction;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function summary(Request $request)
    {
        // 1. Total Barang
        $totalBarang = Item::count();

        // 2. Current Stock (total semua stok)
        $currentStock = Item::sum('current_stock');

        // 3. Barang Keluar (total keseluruhan, gak difilter bulan)
        $barangKeluar = StockHistory::where('type', 'out')->sum('qty');

        // 4. Barang Masuk (total keseluruhan, gak difilter bulan)
        $barangMasuk = StockHistory::where('type', 'in')->sum('qty');

        // 5. Out of Stock
        $outOfStock = Item::where('current_stock', '<=', 0)->count();

        // Tabel kiri: Aktivitas Stok Terbaru (5 terbaru)
        $aktivitasStok = StockHistory::with(['item', 'user'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($history) {
                return [
                    'date' => $history->date,
                    'part_number' => $history->item->part_number ?? '-',
                    'name' => $history->item->name ?? '-',
                    'type' => $history->type,
                    'qty' => $history->qty,
                    'user_name' => $history->user->name ?? '-',
                ];
            });

        // Tabel kanan: Transaksi Terbaru (5 terbaru)
        $transaksiTerbaru = Transaction::with(['employes.user', 'transaction_items.item'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($transaction) {
                $itemNames = $transaction->transaction_items
                    ->map(fn($ti) => $ti->item->name ?? null)
                    ->filter()
                    ->implode(', ');

                return [
                    'transaction_number' => $transaction->transaction_number,
                    'employe_name' => $transaction->employes->user->name ?? '-',
                    'barang' => $itemNames,
                    'date' => $transaction->date,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Data Dashboard Ditemukan',
            'data' => [
                'summary' => [
                    'total_barang' => $totalBarang,
                    'current_stock' => (int) $currentStock,
                    'barang_keluar' => (int) $barangKeluar,
                    'barang_masuk' => (int) $barangMasuk,
                    'out_of_stock' => $outOfStock,
                ],
                'aktivitas_stok' => $aktivitasStok,
                'transaksi_terbaru' => $transaksiTerbaru,
            ],
        ]);
    }
} 