<?php

namespace App\Http\Controllers;

use App\Models\Employes;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TurnoverController extends Controller
{
    /**
     * Employee turnover rate.
     *   rate = karyawan keluar / rata-rata headcount * 100
     *   rata-rata headcount = (headcount awal periode + headcount akhir periode) / 2
     *
     * ?year=2026            -> per tahun
     * ?year=2026&month=10   -> per bulan
     */
    public function index(Request $request)
    {
        $data = $request->validate([
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'month' => ['nullable', 'integer', 'between:1,12'],
        ]);

        $year = (int) ($data['year'] ?? now()->year);
        $month = isset($data['month']) ? (int) $data['month'] : null;

        // tanggal masuk = contract_start, kalau kosong pakai tanggal data dibuat
        // tanggal keluar = left_at, kalau karyawan inactive tapi belum ada tanggal pakai tanggal terakhir diubah
        $people = Employes::query()
            ->select(['id', 'user_id', 'division', 'position', 'status', 'contract_start', 'left_at', 'created_at', 'updated_at'])
            ->with('user:id,name')
            ->get()
            ->map(function ($e) {
                $left = $e->left_at
                    ?? ($e->status === 'inactive' ? $e->updated_at->copy() : null);

                return [
                    'id' => $e->id,
                    'name' => $e->user->name ?? '-',
                    'division' => $e->division,
                    'position' => $e->position,
                    'joined' => ($e->contract_start ?? $e->created_at)->copy()->startOfDay(),
                    'left' => $left?->copy()->startOfDay(),
                ];
            });

        // headcount pada akhir hari $date
        $headcountAt = fn (Carbon $date) => $people->filter(
            fn ($p) => $p['joined']->lte($date) && ($p['left'] === null || $p['left']->gt($date))
        )->count();

        $calc = function (Carbon $from, Carbon $to) use ($people, $headcountAt) {
            $start = $headcountAt($from->copy()->subDay());
            $end = $headcountAt($to);
            $avg = ($start + $end) / 2;
            $left = $people->filter(fn ($p) => $p['left'] && $p['left']->between($from, $to))->count();
            $joined = $people->filter(fn ($p) => $p['joined']->between($from, $to))->count();

            return [
                'headcount_start' => $start,
                'headcount_end' => $end,
                'average_headcount' => round($avg, 1),
                'joined' => $joined,
                'left' => $left,
                'turnover_rate' => $avg > 0 ? round($left / $avg * 100, 2) : 0.0,
            ];
        };

        // periode terpilih
        if ($month) {
            $from = Carbon::create($year, $month, 1)->startOfDay();
            $to = $from->copy()->endOfMonth()->startOfDay();
            $label = $from->locale('id')->translatedFormat('F Y');
        } else {
            $from = Carbon::create($year, 1, 1)->startOfDay();
            $to = Carbon::create($year, 12, 31)->startOfDay();
            $label = "Tahun {$year}";
        }

        // rincian per bulan dalam tahun terpilih (bulan yang belum terjadi dikosongkan)
        $today = now()->startOfDay();
        $monthly = collect(range(1, 12))->map(function ($m) use ($year, $today, $calc) {
            $mFrom = Carbon::create($year, $m, 1)->startOfDay();
            $mTo = $mFrom->copy()->endOfMonth()->startOfDay();

            if ($mFrom->gt($today)) {
                return ['month' => $m, 'future' => true, 'left' => 0, 'joined' => 0, 'headcount_end' => null, 'turnover_rate' => null];
            }

            $r = $calc($mFrom, $mTo);

            return [
                'month' => $m,
                'future' => false,
                'left' => $r['left'],
                'joined' => $r['joined'],
                'headcount_end' => $r['headcount_end'],
                'turnover_rate' => $r['turnover_rate'],
            ];
        })->values();

        $leftEmployees = $people
            ->filter(fn ($p) => $p['left'] && $p['left']->between($from, $to))
            ->sortBy('left')
            ->map(fn ($p) => [
                'id' => $p['id'],
                'name' => $p['name'],
                'division' => $p['division'],
                'position' => $p['position'],
                'left_at' => $p['left']->format('Y-m-d'),
            ])
            ->values();

        // pilihan tahun: dari tahun paling awal data sampai tahun ini
        $firstYear = $people->map(fn ($p) => $p['joined']->year)->min() ?? now()->year;
        $years = range(now()->year, min($firstYear, now()->year));

        return response()->json([
            'success' => true,
            'message' => 'Data Turn Over Rate Ditemukan',
            'data' => [
                'period' => ['year' => $year, 'month' => $month, 'label' => $label],
                'summary' => $calc($from, $to),
                'monthly' => $monthly,
                'left_employees' => $leftEmployees,
                'years' => $years,
            ],
        ]);
    }
}
