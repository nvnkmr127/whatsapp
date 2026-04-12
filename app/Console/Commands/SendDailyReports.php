<?php

namespace App\Console\Commands;

use App\Mail\DailySummaryReport;
use App\Models\Message;
use App\Models\Team;
use App\Models\Conversation;
use App\Models\User;
use App\Services\Email\EmailDispatcher;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendDailyReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-daily-reports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily activity reports to all team owners and admins at 11 PM';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting daily report distribution...');
        
        $yesterday = Carbon::yesterday();
        $dateString = $yesterday->toDateString(); // Pass ISO date string
        
        Team::chunk(100, function ($teams) use ($dateString) {
            foreach ($teams as $team) {
                \App\Jobs\SendTeamDailyReportJob::dispatch($team->id, $dateString);
            }
            $this->info("Dispatched reports for " . $teams->count() . " teams.");
        });
        
        $this->info('Daily report distribution complete.');
    }

}
