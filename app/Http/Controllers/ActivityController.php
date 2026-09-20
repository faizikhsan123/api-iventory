<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreActivityRequest;
use App\Http\Requests\UpdateActivityRequest;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use Illuminate\Http\Request;

class ActivityController extends Controller
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

        $query = Activity::query()->with('user');

        if ($request->filled('search')) {
            $query->where('detail', 'like', '%'.$request->input('search').'%');
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
            // code...
        }

        $activity = $query->latest()->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Data Activity Ditemukan',
            'data' => ActivityResource::collection($activity),
            'meta' => [
                'current_page' => $activity->currentPage(),
                'last_page' => $activity->lastPage(),
                'total' => $activity->total(),
                'per_page' => $activity->perPage(),
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreActivityRequest $request) {}

    /**
     * Display the specified resource.
     */
    public function show(Activity $activity)
    {
        $activity->load('user');

        return response()->json([
            'status' => 'success',
            'message' => 'Data Activity Ditemukan',
            'data' => new ActivityResource($activity),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Activity $activity)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateActivityRequest $request, Activity $activity)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Activity $activity)
    {
        //
    }
}
