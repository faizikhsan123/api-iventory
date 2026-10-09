<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoregroupRequest;
use App\Http\Requests\UpdategroupRequest;
use App\Http\Resources\GroupResource;
use App\Models\Employes;
use App\Models\group;

class GroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {   
        $groups = group::with('employes.user')->latest()->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Data Group Ditemukan',
            'data' => GroupResource::collection($groups),
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
    public function store(StoregroupRequest $request)
    {
        $data = $request->validated();

        $group = group::create([
            'name_group' => $data['name_group'],
           
        ]);

        // jika ada employes_ids, maka update group_id di tabel employes 
        if (! empty($data['employes_ids'])) {
            Employes::whereIn('id', $data['employes_ids'])
                ->update(['group_id' => $group->id]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Data Group Berhasil Ditambahkan',
            'data' => new GroupResource($group->load('employes')),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(group $group)
    {
        $group->load('employes.user');

        return response()->json([
            'status' => 'success',
            'message' => 'Data Group Ditemukan',
            'data' => new GroupResource($group),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(group $group)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdategroupRequest $request, group $group)
    {
        $data = $request->validated();

        // update data group
        $group->update($request->safe()->only(['name_group']));

        // jika ada employes_ids, maka update group_id di tabel employes
        if ($request->has('employes_ids')) {
            // lepas semua anggota lama
            $group->employes()->update(['group_id' => null]);

            // pasang anggota baru
            Employes::whereIn('id', $data['employes_ids'])
                ->update(['group_id' => $group->id]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Data Group Berhasil Diupdate',
            'data' => new GroupResource($group->load('employes')),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(group $group)
    {
        // lepas semua anggota
        $group->employes()->update(['group_id' => null]);

        $group->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Data Group Berhasil Dihapus',
        ]);
    }
}
