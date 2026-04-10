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
    public function handle(EmailDispatcher $emailDispatcher)
    {
        $this->info('Starting daily report distribution...');
        
        $yesterday = Carbon::yesterday();
        $dateString = $yesterday->format('F j, Y');
        
        $teams = Team::all();
        
        foreach ($teams as $team) {
            try {
                $stats = $this->getTeamStats($team, $yesterday);
                
                // Identify recipients (Owner + Admins)
                $recipients = $team->users()->wherePivot('role', 'admin')->get();
                if ($team->user_id) {
                    $owner = User::find($team->user_id);
                    if ($owner && !$recipients->contains($owner)) {
                        $recipients->push($owner);
                    }
                }
                
                foreach ($recipients as $recipient) {
                    $emailDispatcher->send(
                        $recipient,
                        \App\Enums\EmailUseCase::NOTIFICATION,
                        new DailySummaryReport($team, $stats, $dateString)
                    );
                }
                
                $this->info("Reports sent for Team: {$team->name}");
            } catch (\Exception $e) {
                Log::error("Failed to send daily report for Team {$team->id}: " . $e->getMessage());
                $this->error("Error for Team {$team->id}: " . $e->getMessage());
            }
        }
        
        $this->info('Daily report distribution complete.');
    }

    protected function getTeamStats(Team $team, Carbon $date): array
    {
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        // Messaging
        $sent = Message::where('team_id', $team->id)
            ->where('direction', 'outbound')
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->count();
            
        $delivered = Message::where('team_id', $team->id)
            ->where('direction', 'outbound')
            ->where('status', 'delivered')
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->count();
            
        $read = Message::where('team_id', $team->id)
            ->where('direction', 'outbound')
            ->whereIn('status', ['read', 'viewed'])
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->count();
            
        $inbound = Message::where('team_id', $team->id)
            ->where('direction', 'inbound')
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->count();

        // Conversations
        $newConversations = Conversation::where('team_id', $team->id)
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->count();
            
        $activeConversations = Conversation::where('team_id', $team->id)
            ->whereNull('closed_at')
            ->count();

        // Automation & AI
        $automationRuns = \App\Models\AutomationRun::whereHas('automation', fn($q) => $q->where('team_id', $team->id))
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->count();
            
        $aiInteractions = \App\Models\ActivityLog::where('team_id', $team->id)
            ->where('action', 'ai_interaction')
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->count();

        // Calls
        $calls = \App\Models\WhatsAppCall::where('team_id', $team->id)
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->get();
        $callCount = $calls->count();
        $callMinutes = round($calls->where('status', 'completed')->sum('duration_seconds') / 60, 1);

        // Top 3 Contacts
        $topContacts = \App\Models\Message::where('team_id', $team->id)
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->select('contact_id', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('contact_id')
            ->orderByDesc('count')
            ->limit(3)
            ->get()
            ->map(function($msg) {
                return [
                    'name' => $msg->contact->name ?? $msg->contact->phone_number ?? 'Unknown',
                    'count' => $msg->count
                ];
            })->toArray();

        // Billing
        $wallet = $team->wallet; 
        $balance = $wallet ? $wallet->balance : 0;

        return [
            'sent' => $sent,
            'delivered' => $delivered,
            'read' => $read,
            'inbound' => $inbound,
            'delivery_rate' => $sent > 0 ? round(($delivered / $sent) * 100, 1) : 0,
            'read_rate' => $delivered > 0 ? round(($read / $delivered) * 100, 1) : 0,
            'new_conversations' => $newConversations,
            'active_conversations' => $activeConversations,
            'automation_runs' => $automationRuns,
            'ai_interactions' => $aiInteractions,
            'calls' => [
                'count' => $callCount,
                'minutes' => $callMinutes
            ],
            'top_contacts' => $topContacts,
            'billing' => [
                'balance' => $balance
            ]
        ];
    }
}
