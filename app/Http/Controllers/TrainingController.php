<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTrainingRequest;
use App\Http\Requests\UpdateTrainingRequest;
use App\Http\Resources\Trainingresource;
use App\Models\Training;
use Illuminate\Support\Facades\Storage;

class TrainingController extends Controller
{
    public function index()
    {
        $trainings = Training::withCount('participants')->latest()->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Data Training Ditemukan',
            'data' => Trainingresource::collection($trainings),
        ]);
    }

    public function store(StoreTrainingRequest $request)
    {
        $training = Training::create($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Data Training Berhasil Ditambahkan',
            'data' => new Trainingresource($training),
        ], 201);
    }

    public function show(Training $training)
    {
        $training->loadCount('participants');

        return response()->json([
            'status' => 'success',
            'message' => 'Data Training Ditemukan',
            'data' => new Trainingresource($training),
        ]);
    }

    public function update(UpdateTrainingRequest $request, Training $training)
    {
        $training->update($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Data Training Berhasil Diubah',
            'data' => new Trainingresource($training),
        ]);
    }

    public function destroy(Training $training)
    {
        // baris peserta ikut terhapus (cascade), file fisiknya dibersihin manual
        Storage::disk('public')->deleteDirectory("trainings/{$training->id}");

        $training->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Data Training Berhasil Dihapus',
        ]);
    }
}
