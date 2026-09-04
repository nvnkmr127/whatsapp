<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $teamId = $request->user()->currentTeam?->id ?? $request->user()->current_team_id;
        $query = ActivityLog::where('team_id', $teamId)
            ->with(['user', 'subject'])
            ->latest();

        if ($request->has('type')) {
            $query->where('description', 'like', '%' . $request->type . '%');
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }

        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('description', 'like', '%' . $request->search . '%')
                  ->orWhereHas('user', function($uq) use ($request) {
                      $uq->where('name', 'like', '%' . $request->search . '%');
                  });
            });
        }

        return response()->json($query->paginate(20));
    }

    public function show(Request $request, ActivityLog $activity)
    {
        $teamId = $request->user()->currentTeam?->id ?? $request->user()->current_team_id;
        if ($activity->team_id !== $teamId) {
            abort(403);
        }

        $activity->load(['user', 'subject']);
        return response()->json($activity);
    }
}
