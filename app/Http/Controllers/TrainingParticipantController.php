<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreParticipantsRequest;
use App\Http\Requests\UpdateParticipantRequest;
use App\Http\Resources\TrainingParticipantResource;
use App\Models\Training;
use App\Models\TrainingParticipant;
use Illuminate\Support\Facades\Storage;

class TrainingParticipantController extends Controller
{
    public function index(Training $training)
    {
        $participants = $training->participants()->with('employes.user')->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Data Peserta Ditemukan',
            'data' => TrainingParticipantResource::collection($participants),
        ]);
    }

    // assign bulk: yang sudah jadi peserta dibiarkan, yang baru ditambah
    public function store(StoreParticipantsRequest $request, Training $training)
    {
        $date = $request->validated()['date'];

        foreach ($request->validated()['employes_ids'] as $employesId) {
            $training->participants()->firstOrCreate(
                ['employes_id' => $employesId],
                ['date' => $date],
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Peserta Berhasil Ditambahkan',
            'data' => TrainingParticipantResource::collection(
                $training->participants()->with('employes.user')->get()
            ),
        ], 201);
    }

    // edit tanggal / catatan, file opsional (ganti kalau dikirim)
    public function update(UpdateParticipantRequest $request, TrainingParticipant $participant)
    {
        $data = $request->safe()->only(['date', 'notes']);

        if ($request->hasFile('file')) {
            if ($participant->file) {
                Storage::disk('public')->delete($participant->file);
            }

            $data['file'] = $request->file('file')->store("trainings/{$participant->training_id}", 'public');
        }

        $participant->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Data Peserta Berhasil Diubah',
            'data' => new TrainingParticipantResource($participant->load('employes.user')),
        ]);
    }

    public function showFile(TrainingParticipant $participant)
    {
        abort_unless(
            $participant->file && Storage::disk('public')->exists($participant->file),
            404,
            'File tidak ditemukan'
        );

        return response()->file(storage_path('app/public/' . $participant->file));
    }

    public function destroy(TrainingParticipant $participant)
    {
        if ($participant->file) {
            Storage::disk('public')->delete($participant->file);
        }

        $participant->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Peserta Berhasil Dihapus',
        ]);
    }
}