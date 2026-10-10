<?php

namespace App\Http\Controllers;

use App\Actions\UpsertEmployeeCpd;
use App\Http\Requests\StoreEmployesRequest;
use App\Http\Requests\UpdateEmployesRequest;
use App\Http\Resources\EmployeeCpdResource;
use App\Http\Resources\EmployesResource;
use App\Http\Resources\McuResource;
use App\Models\Activity;
use App\Models\Employes;
use App\Models\Mcu;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmployesController extends Controller
{
    private const PPE_FIELDS = ['ppe_shoes', 'ppe_coverall', 'ppe_wearpack', 'ppe_respirator', 'ppe_vest', 'ppe_gloves'];

    private static function ppeSizes(Employes $e): array
    {
        return [
            'shoes' => $e->ppe_shoes,
            'coverall' => $e->ppe_coverall,
            'wearpack' => $e->ppe_wearpack,
            'respirator' => $e->ppe_respirator,
            'vest' => $e->ppe_vest,
            'gloves' => $e->ppe_gloves,
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = max(1, min(
            $request->integer('per_page', 10),
            100
        ));

        $query = Employes::query()->with('user');

        // filter search nama
        if ($request->filled('search')) {
            $query->whereHas('user', fn ($u) => $u->where('name', 'like', '%'.$request->input('search').'%'));
        }

        // filter division
        if ($request->filled('division')) {
            $query->where('division', $request->input('division'));
        }
        // filter position
        if ($request->filled('position')) {
            $query->where('position', $request->input('position'));
        }

        $employes = $query->latest()->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Data Employes Ditemukan',
            'data' => EmployesResource::collection($employes),
            'meta' => [
                'current_page' => $employes->currentPage(),
                'last_page' => $employes->lastPage(),
                'per_page' => $employes->perPage(),
                'total' => $employes->total(),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    public function detail(Employes $employe)
    {
        $employe->load(['user', 'group', 'cpd'])->loadCount('contractRenewals');

        $transactionItems = $employe->transactionItems()
            ->with(['transaction', 'item'])
            ->latest('id')
            ->get();

        $totalBarang = $transactionItems->sum('qty');

        $mcus = $employe->mcus()->latest('mcu_date')->latest('id')->get();
        Mcu::flagLatest($mcus);

        $participants = $employe->trainingParticipants()->with('training')->latest('date')->latest('id')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'employe' => [
                    'id' => $employe->id,
                    'name' => $employe->user->name ?? '-',
                    'file' => $employe->file,        // <-- tambahin ini
                    'division' => $employe->division,
                    'position' => $employe->position,
                    'status' => $employe->status,
                    'ktp_address' => $employe->ktp_address,
                    'actual_address' => $employe->actual_address,
                    'emergency_contact' => $employe->emergency_contact,
                    'group_name' => $employe->group->name_group ?? null,
                    'contract_renewals' => (int) $employe->contract_renewals_count,
                    'contract_start' => optional($employe->contract_start)->format('Y-m-d'),
                    'contract_end' => optional($employe->contract_end)->format('Y-m-d'),
                    'ppe_sizes' => self::ppeSizes($employe),
                    'cpd' => $employe->cpd ? new EmployeeCpdResource($employe->cpd) : null,
                ],
                'mcus' => McuResource::collection($mcus),
                'trainings' => $participants->map(fn ($p) => [
                    'participant_id' => $p->id,
                    'training_id' => $p->training_id,
                    'id_training' => $p->training?->id_training,
                    'name_training' => $p->training?->name_training,
                    'division_training' => $p->training?->division_training,
                    'by' => $p->training?->by,
                    'date' => $p->date?->format('Y-m-d'),
                    'notes' => $p->notes,
                    'file' => $p->file,
                ])->values(),
                'statistik' => [
                    'total_barang_diterima' => (int) $totalBarang,
                ],
                'riwayat_diberikan' => $transactionItems->map(function ($ti) {
                    return [
                        'transaction_number' => $ti->transaction->transaction_number ?? '-',
                        'date' => $ti->transaction->date ?? '-',
                        'barang' => $ti->item->name ?? '-',
                        'qty' => $ti->qty,
                        'note' => $ti->transaction->note ?? null,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEmployesRequest $request)
    {
        // data ini darri request
        $data = $request->validated();

        $filePath = null;

        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('employees', 'public');
        }

        $employes = DB::transaction(function () use ($data, $filePath) {
            $user = User::create([
                'name' => $data['name'],
            ]);

            $employes = Employes::create([
                'user_id' => $user->id,
                'id_number' => $data['id_number'],
                'file' => $filePath,
                'division' => $data['division'],
                'position' => $data['position'],
                'status' => 'active',
                'contract_start' => $data['contract_start'] ?? null,
                'contract_end' => $data['contract_end'] ?? null,
                'ktp_address' => $data['ktp_address'] ?? null,
                'actual_address' => $data['actual_address'] ?? null,
                'emergency_contact' => $data['emergency_contact'] ?? null,
                ...Arr::only($data, self::PPE_FIELDS),
            ]);

            if (! empty($data['cpd'])) {
                app(UpsertEmployeeCpd::class)->handle($employes, $data['cpd']);
            }

            return $employes;
        });

        // Activity::create([
        //     'user_id' => Auth::user()->id,
        //     'activity' => " Add Employes {$employes['name']}",

        // ]);

        Activity::create([
            'user_id' => Auth::user()->id,
            'activity' => 'Menambah Karyawan',
            'detail' => " Karyawan {$data['name']} berhasil ditambahkan",
            'type' => null,
            'date' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Data Employes Berhasil Ditambahkan',
            'data' => new EmployesResource(
                $employes->load('user', 'cpd')
            ),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Employes $employe)
    {
        $employe->load('user', 'cpd');

        return response()->json([
            'status' => 'success',
            'message' => 'Data Employes Ditemukan',
            'data' => new EmployesResource($employe),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employes $employes)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEmployesRequest $request, Employes $employe)
    {
        $validated = $request->validated();
        $filePath = $employe->file;

        if ($request->hasFile('file')) {

            $filePath = $request->file('file')->store('items', 'public');
        }

        DB::transaction(function () use ($validated, $filePath, $employe) {
            $employe->update([
                'id_number' => $validated['id_number'],
                'file' => $filePath ?? null,
                'division' => $validated['division'],
                'position' => $validated['position'],
                'status' => $validated['status'],
                // inactive: pakai tanggal keluar yang diisi, lalu yang sudah tersimpan, lalu hari ini; active: dikosongkan
                'left_at' => $validated['status'] === 'inactive'
                    ? ($validated['left_at'] ?? $employe->left_at?->format('Y-m-d') ?? now()->toDateString())
                    : null,
                'contract_start' => $validated['contract_start'] ?? null,
                'contract_end' => $validated['contract_end'] ?? null,
                'ktp_address' => $validated['ktp_address'] ?? null,
                'actual_address' => $validated['actual_address'] ?? null,
                'emergency_contact' => $validated['emergency_contact'] ?? null,
                // ukuran APR hanya diubah bila dikirim
                ...Arr::only($validated, self::PPE_FIELDS),
            ]);

            $employe->user()->update([
                'name' => $validated['name'],
                // 'email' => $validated['email'],

                // 'password' => bcrypt($validated['password']),
            ]);

            if (! empty($validated['cpd'])) {
                app(UpsertEmployeeCpd::class)->handle($employe, $validated['cpd']);
            }
        });

        Activity::create([
            'user_id' => Auth::user()->id,
            'activity' => 'Mengubah Data Karyawan',
            'detail' => " Data Karyawan {$employe['name']} Berhasil Dirubah",
            'type' => null,
            'date' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Data Employes Berhasil Diubah',
            'data' => new EmployesResource($employe->fresh()->load(['user', 'cpd'])),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employes $employe)
    {
        $employe->delete();
        Activity::create([
            'user_id' => Auth::user()->id,
            'activity' => 'Menghapus Karyawan',
            'detail' => "Karyawan {$employe['name']} Berhasil Dihapus",
            'type' => null,
            'date' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Data Employes Berhasil Dihapus',
        ]);
    }
}
