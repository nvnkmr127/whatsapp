<?php

namespace App\Http\Controllers;

use App\Models\ConsentLog;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ComplianceController extends Controller
{
    /**
     * Display the Compliance Logs (Consent History).
     */
    public function logs()
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-settings');

        $team = Auth::user()->currentTeam;

        if (! $team) {
            return view('compliance.logs', ['logs' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20)]);
        }

        $logs = ConsentLog::where('team_id', $team->id)
            ->with(['contact'])
            ->latest()
            ->paginate(20);

        return view('compliance.logs', compact('logs'));
    }

    /**
     * Display the Consent Registry (Current Status).
     */
    public function registry(Request $request)
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-settings');

        $request->validate([
            'status' => 'nullable|string|in:opt_in,opt_out,none',
        ]);

        $team = Auth::user()->currentTeam;

        if (! $team) {
            return view('compliance.registry', ['contacts' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20)]);
        }

        $query = Contact::where('team_id', $team->id);

        $status = $request->get('status');
        if ($status) {
            $query->where('opt_in_status', $status);
        }

        $contacts = $query->paginate(20);

        return view('compliance.registry', compact('contacts'));
    }
}
