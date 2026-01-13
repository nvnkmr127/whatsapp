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

        $logs = ConsentLog::where('team_id', Auth::user()->currentTeam->id)
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

        $query = Contact::where('team_id', Auth::user()->currentTeam->id);

        $status = $request->get('status');
        if ($status) {
            $query->where('opt_in_status', $status);
        }

        $contacts = $query->paginate(20);

        return view('compliance.registry', compact('contacts'));
    }
}
