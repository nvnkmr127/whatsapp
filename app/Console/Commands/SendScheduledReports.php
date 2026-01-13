<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ScheduledReport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendScheduledReports extends Command
{
    protected $signature = 'reports:send';
    protected $description = 'Send scheduled analytics reports to users via email';

    public function handle()
    {
        $this->info('Checking for due reports...');

        $reports = ScheduledReport::all();

        foreach ($reports as $report) {
            // Check Frequency logic (Simplified for MVP: just check if NULL or > 1 min ago for testing, ideally > 7 days)
            // Let's assume 'weekly' means "it's Monday" or "7 days passed".
            $shouldSend = false;
            if (!$report->last_sent_at) {
                $shouldSend = true;
            } else {
                // If weekly, check 7 days
                if ($report->frequency === 'weekly' && $report->last_sent_at->diffInDays(now()) >= 7) {
                    $shouldSend = true;
                }
            }

            if ($shouldSend) {
                $this->info("Sending report to User {$report->user_id}");

                // Generate CSV Content
                $csvData = "Date,Type,Amount\n";
                // Fetch some stats...
                $txns = \App\Models\TeamTransaction::where('team_id', $report->team_id)->latest()->take(20)->get();
                foreach ($txns as $txn) {
                    $csvData .= "{$txn->created_at},{$txn->type},{$txn->amount}\n";
                }

                // MOCK EMAIL SENDING
                // In real app: Mail::to($report->user)->send(new ReportEmail($csvData));
                Log::info("Sent Weekly Report to {$report->user_id}");

                // Update
                $report->update(['last_sent_at' => now()]);
            }
        }
    }
}
