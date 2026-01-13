<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AutoClearChatController extends Controller
{
    public function update(Request $request)
    {
        $validated = $request->validate([
            'enable_auto_clear_chat'  => 'boolean',
            'auto_clear_history_time' => 'required_if:enable_auto_clear_chat,1|nullable|integer|min:1',
        ]);

        $settings = [
            'enable_auto_clear_chat'  => $validated['enable_auto_clear_chat']  ?? false,
            'auto_clear_history_time' => $validated['auto_clear_history_time'] ?? null,
        ];

        // Update the settings using your existing function
        set_settings_batch('whats-mark', $settings);

        // Check if cron is properly configured
        $lastCronRun    = get_setting('cron-job.last_cron_run');
        $cronConfigured = $lastCronRun && Carbon::parse($lastCronRun)->isAfter(Carbon::now()->subDay());

        if ($validated['enable_auto_clear_chat'] && ! $cronConfigured) {
            return redirect()->back()->with('warning', 'Settings saved, but cron job might not be configured properly. Please check your server configuration.');
        }

        return redirect()->back()->with('success', 'Auto clear chat history settings updated successfully.');
    }

    public function index()
    {
        $settings = get_settings_by_group('whats-mark');

        return view('admin.settings.auto-clear-chat', [
            'enable_auto_clear_chat'  => $settings->enable_auto_clear_chat  ?? false,
            'auto_clear_history_time' => $settings->auto_clear_history_time ?? null,
        ]);
    }
}
