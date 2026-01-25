<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use Illuminate\Http\Request;

class EmailLogController extends Controller
{
    public function index(Request $request)
    {
        $query = EmailLog::with(['template', 'smtpConfig'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('use_case')) {
            $query->where('use_case', $request->use_case);
        }

        if ($request->filled('search')) {
            $query->where('recipient', 'like', '%' . $request->search . '%')
                ->orWhere('subject', 'like', '%' . $request->search . '%');
        }

        $logs = $query->paginate(20);

        return view('admin.email-logs.index', compact('logs'));
    }

    public function show(EmailLog $log)
    {
        return view('admin.email-logs.show', compact('log'));
    }
}
