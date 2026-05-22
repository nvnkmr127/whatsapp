<?php

namespace App\Console\Commands;

use App\Models\ScheduledReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendScheduledReports extends Command
{
    protected $signature = 'reports:send';

    protected $description = 'Send scheduled analytics reports to users via email';

    public function handle()
    {
        $this->info('Checking for due reports...');

        $reports = ScheduledReport::where(function ($q): void {
            $q->whereNull('last_sent_at')
                ->orWhere(function ($q): void {
                    $q->where('frequency', 'weekly')
                        ->where('last_sent_at', '<=', now()->subDays(7));
                });
        })->cursor();

        foreach ($reports as $report) {
            $this->info("Sending report to User {$report->user_id}");

            $csvData = "Date,Type,Amount\n";
            $txns = \App\Models\TeamTransaction::where('team_id', $report->team_id)->latest()->take(20)->get();
            foreach ($txns as $txn) {
                $csvData .= "{$txn->created_at},{$txn->type},{$txn->amount}\n";
            }

            // TODO: Mail::to($report->user)->send(new ReportEmail($csvData));
            Log::info("Sent Weekly Report to {$report->user_id}");

            $report->update(['last_sent_at' => now()]);
        }
    }
}
