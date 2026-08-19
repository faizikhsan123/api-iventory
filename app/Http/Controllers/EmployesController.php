<?php

namespace App\Http\Controllers;

use App\Models\Employes;
use App\Http\Requests\StoreEmployesRequest;
use App\Http\Requests\UpdateEmployesRequest;
use App\Http\Resources\EmployesResource;
use App\Models\User;

class EmployesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $employes = Employes::with('user')->latest()->get();
        return response()->json([
            'status' => 'success',
            'message' => 'Data Employes Ditemukan',
            'data' => EmployesResource::collection($employes)
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
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
            'status' => $data['status'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Data Employes Berhasil Ditambahkan',
            'data' => new EmployesResource(
                $employes->load('user')
            )
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Employes $employes)
    {
        //
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
    public function update(UpdateEmployesRequest $request, Employes $employes)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employes $employes)
    {
        //
    }
}
