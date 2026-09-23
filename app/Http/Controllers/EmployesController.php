<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployesRequest;
use App\Http\Requests\UpdateEmployesRequest;
use App\Http\Resources\EmployesResource;
use App\Models\Activity;
use App\Models\Employes;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = max(1, min(
            $request->integer('per_page', 10),
            100
        ));

        $query = Employes::query()
            ->with([
                'user',
                'transactionItems.item',
                'transactionItems.transaction',
            ])
            ->withSum('transactionItems as given_items_count', 'qty');

        // filter search nama
        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->input('search').'%');
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
        $employe->load('user');

        // ambil transaction_items langsung lewat relasi hasManyThrough,
        // sekalian load transaction & item-nya biar dapet nomor transaksi + nama barang
        $transactionItems = $employe->transactionItems()
            ->with(['transaction', 'item'])
            ->latest('id') // urutkan berdasarkan tanggal transaksi
            ->get();

        $totalBarang = $transactionItems->sum('qty');

        return response()->json([
            'success' => true,
            'data' => [
                'employe' => [
                    'id' => $employe->id,
                    'name' => $employe->user->name ?? '-',
                    'division' => $employe->division,
                    'position' => $employe->position,
                    'status' => $employe->status,
                ],
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

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        $employes = Employes::create([
            'user_id' => $user->id,
            'division' => $data['division'],
            'position' => $data['position'],
            'status' => 'active',
        ]);

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
                $employes->load('user')
            ),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Employes $employe)
    {
        $employe->load('user');

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

        $employe->update([
            'division' => $validated['division'],
            'position' => $validated['position'],
            'status' => $validated['status'],
        ]);

        $employe->user()->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
        ]);

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
            'data' => new EmployesResource($employe->fresh()->load('user')),
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
