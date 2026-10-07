<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTrainingRequest;
use App\Http\Requests\UpdateTrainingRequest;
use App\Http\Resources\Trainingresource;
use App\Models\Training;

class TrainingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $training = Training::latest()->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Data Training Ditemukan',
            'data' => Trainingresource::collection($training),
        ]);
    }

    public function store(StoreTrainingRequest $request)
    {
        $training = Training::create($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Data Training Berhasil Ditambahkan',
            'data' => new Trainingresource($training->load('employes.user')),
        ], 201);
    }

    public function show(Training $training)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Data Training Ditemukan',
            'data' => new Trainingresource($training->load('employes.user')),
        ]);
    }

    public function update(UpdateTrainingRequest $request, Training $training)
    {
        // $training->update($request->safe()->only(['id_training', 'division_training', 'name_training']));

        // // assign karyawan: sync tambah yang baru, lepas yang nggak ada di list
        // if ($request->has('employes_ids')) {
        //     $training->employes()->sync($request->input('employes_ids'));
        // }
        $training->update($request->safe()->only([
            'id_training',
            'division_training',
            'name_training',
            'created_by',
            'date'
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'Data Training Berhasil Diubah',
            'data' => new Trainingresource($training->load('employes.user')),
        ]);
    }

    public function destroy(Training $training)
    {
        $training->delete(); // baris pivot ikut terhapus (cascadeOnDelete)

        return response()->json([
            'status' => 'success',
            'message' => 'Data Training Berhasil Dihapus',
        ]);
    }
}
