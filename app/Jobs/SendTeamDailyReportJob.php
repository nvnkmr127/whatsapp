<?php

namespace App\Jobs;

use App\Mail\DailySummaryReport;
use App\Models\Team;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\User;
use App\Services\Email\EmailDispatcher;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendTeamDailyReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $teamId,
        public string $dateString
    ) {}

    public function handle(EmailDispatcher $emailDispatcher): void
    {
        $team = Team::find($this->teamId);
        if (!$team) return;

        $date = Carbon::parse($this->dateString);
        $stats = $this->getTeamStats($team, $date);
        
        // Identify recipients (Owner + Admins)
        $recipients = $team->users()->wherePivot('role', 'admin')->get();
        if ($team->user_id) {
            $owner = User::find($team->user_id);
            if ($owner && !$recipients->contains($owner)) {
                $recipients->push($owner);
            }
        }
        
        $dateLabel = $date->format('F j, Y');

        foreach ($recipients as $recipient) {
            try {
                $emailDispatcher->send(
                    $recipient,
                    \App\Enums\EmailUseCase::NOTIFICATION,
                    new DailySummaryReport($team, $stats, $dateLabel)
                );
            } catch (\Exception $e) {
                Log::error("Failed to send daily report email to {$recipient->email} for Team {$team->id}: " . $e->getMessage());
            }
        }
    }

    protected function getTeamStats(Team $team, Carbon $date): array
    {
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        // Optimized messaging stats in one query if possible (or at least cleaner)
        $messageStats = Message::where('team_id', $team->id)
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->selectRaw("
                count(CASE WHEN direction = 'outbound' THEN 1 END) as sent,
                count(CASE WHEN direction = 'outbound' AND status = 'delivered' THEN 1 END) as delivered,
                count(CASE WHEN direction = 'outbound' AND status IN ('read', 'viewed') THEN 1 END) as read_count,
                count(CASE WHEN direction = 'inbound' THEN 1 END) as inbound
            ")
            ->first();

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
        $topContacts = Message::where('team_id', $team->id)
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->select('contact_id', DB::raw('count(*) as count'))
            ->groupBy('contact_id')
            ->orderByDesc('count')
            ->limit(3)
            ->with('contact:id,name,phone_number')
            ->get()
            ->map(function($msg) {
                return [
                    'name' => $msg->contact->name ?? $msg->contact->phone_number ?? 'Unknown',
                    'count' => $msg->count
                ];
            })->toArray();

        // Billing
        $balance = $team->wallet?->balance ?? 0;

        return [
            'sent' => $messageStats->sent ?? 0,
            'delivered' => $messageStats->delivered ?? 0,
            'read' => $messageStats->read_count ?? 0,
            'inbound' => $messageStats->inbound ?? 0,
            'delivery_rate' => $messageStats->sent > 0 ? round(($messageStats->delivered / $messageStats->sent) * 100, 1) : 0,
            'read_rate' => $messageStats->delivered > 0 ? round(($messageStats->read_count / $messageStats->delivered) * 100, 1) : 0,
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
