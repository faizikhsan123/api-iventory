<?php

namespace App\Http\Controllers;

use App\Models\Employes;
use App\Models\Item;
use App\Models\Mcu;
use App\Models\StockHistory;
use App\Models\Transaction;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    // ambang pengingat kontrak berakhir & MCU berikutnya (hari)
    public const DUE_DAYS = 30;

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
                    'unit' => $history->item->unit ?? '-',
                    'user_name' => $history->user->name ?? '-',

                ];
            });

        // Tabel kanan: Transaksi Terbaru (5 terbaru)
        $transaksiTerbaru = Transaction::with(['employes.user', 'transaction_items.item'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($transaction) {
                $items = $transaction->transaction_items->pluck('item')->filter();

                return [
                    'transaction_number' => $transaction->transaction_number,
                    'employe_name' => $transaction->employes->user->name ?? '-',
                    'barang' => $items->pluck('name')->implode(', '),
                    'category' => $items->pluck('category')->filter()->unique()->implode(', '),
                    'date' => $transaction->date,
                ];
            });

        // Kontrak karyawan aktif yang berakhir <= 30 hari lagi (termasuk yang sudah lewat)
        $today = now()->startOfDay();
        $kontrakBerakhir = Employes::with(['user', 'group'])
            ->where('status', 'active')
            ->whereNotNull('contract_end')
            ->whereDate('contract_end', '<=', $today->copy()->addDays(self::DUE_DAYS))
            ->orderBy('contract_end')
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'name' => $e->user->name ?? '-',
                'id_number' => $e->id_number,
                'division' => $e->division,
                'position' => $e->position,
                'group_name' => $e->group->name_group ?? null,
                'contract_end' => $e->contract_end->format('Y-m-d'),
                'days_left' => (int) $today->diffInDays($e->contract_end->startOfDay(), false),
            ]);

        // MCU berikutnya <= 30 hari lagi (termasuk lewat); hanya MCU terbaru tiap karyawan aktif
        $mcuBerikutnya = Mcu::with(['employes.user', 'employes.group'])
            ->whereHas('employes', fn ($q) => $q->where('status', 'active'))
            ->whereNotNull('next_mcu_date')
            ->whereDate('next_mcu_date', '<=', $today->copy()->addDays(self::DUE_DAYS))
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')->from('mcus as newer')
                    ->whereColumn('newer.employes_id', 'mcus.employes_id')
                    ->where(function ($w) {
                        $w->whereColumn('newer.mcu_date', '>', 'mcus.mcu_date')
                            ->orWhere(function ($same) {
                                $same->whereColumn('newer.mcu_date', 'mcus.mcu_date')
                                    ->whereColumn('newer.id', '>', 'mcus.id');
                            });
                    });
            })
            ->orderBy('next_mcu_date')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'employes_id' => $m->employes_id,
                'name' => $m->employes->user->name ?? '-',
                'id_number' => $m->employes->id_number,
                'division' => $m->employes->division,
                'position' => $m->employes->position,
                'group_name' => $m->employes->group->name_group ?? null,
                'place_name' => $m->place_name,
                'next_mcu_date' => $m->next_mcu_date->format('Y-m-d'),
                'days_left' => (int) $today->diffInDays($m->next_mcu_date->copy()->startOfDay(), false),
            ]);

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
                'kontrak_berakhir' => $kontrakBerakhir,
                'mcu_berikutnya' => $mcuBerikutnya,
            ],
        ]);
    }
}
