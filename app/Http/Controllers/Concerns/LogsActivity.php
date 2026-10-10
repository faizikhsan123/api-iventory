<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Activity;
use Illuminate\Support\Facades\Auth;

// Catat aksi user ke Activity Log (satu tempat, tidak disalin di tiap controller).
trait LogsActivity
{
    protected function logActivity(string $activity, string $detail): void
    {
        Activity::create([
            'user_id' => Auth::id(),
            'activity' => $activity,
            'detail' => $detail,
            'type' => null,
            'date' => now(),
        ]);
    }
}
