<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\LogsActivity;
use App\Http\Requests\StorePerformanceReviewRequest;
use App\Models\Employes;
use App\Models\PerformanceReview;

class PerformanceReviewController extends Controller
{
    use LogsActivity;

    public function index(Employes $employe)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Data Performance Review Ditemukan',
            'data' => $employe->performanceReviews()->latest('review_date')->latest('id')->get(),
        ]);
    }

    public function store(StorePerformanceReviewRequest $request, Employes $employe)
    {
        $data = $request->validated();

        $avg = fn (array $values) => round(array_sum($values) / count($values), 2);

        $scores = [
            'safety' => array_map('intval', $data['scores']['safety']),
            'production' => array_map('intval', $data['scores']['production']),
            'cost' => array_map('intval', $data['scores']['cost']),
        ];

        $safety = $avg($scores['safety']);
        $production = $avg($scores['production']);
        $cost = $avg($scores['cost']);

        $review = $employe->performanceReviews()->create([
            ...collect($data)->except('scores')->all(),
            'scores' => $scores,
            'safety_avg' => $safety,
            'production_avg' => $production,
            'cost_avg' => $cost,
            'overall_avg' => round(($safety + $production + $cost) / 3, 2),
        ]);

        $name = $employe->user->name ?? $employe->id;
        $this->logActivity('Menambah Performance Review', "Performance review karyawan {$name} berhasil ditambahkan");

        return response()->json([
            'status' => 'success',
            'message' => 'Performance Review Berhasil Ditambahkan',
            'data' => $review,
        ], 201);
    }

    public function destroy(PerformanceReview $performanceReview)
    {
        $performanceReview->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Performance Review Berhasil Dihapus',
        ]);
    }
}
