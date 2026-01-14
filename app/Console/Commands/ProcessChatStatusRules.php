<?php

namespace App\Console\Commands;

use App\Models\Contact;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessChatStatusRules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chats:process-status-rules';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process automated chat status transitions based on team rules';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $teams = Team::whereNotNull('chat_status_rules')->get();

        foreach ($teams as $team) {
            $rules = $team->chat_status_rules;
            if (empty($rules))
                continue;

            foreach ($rules as $rule) {
                $statusIn = $rule['status_in'] ?? null;
                $afterDays = (int) ($rule['after_days'] ?? 0);
                $statusTo = $rule['status_to'] ?? null;

                if (!$statusIn || !$statusTo || $afterDays <= 0)
                    continue;

                // Find contacts in this team with statusIn that haven't been updated in X days
                $contacts = Contact::where('team_id', $team->id)
                    ->where('status', $statusIn)
                    ->where('updated_at', '<=', Carbon::now()->subDays($afterDays))
                    ->get();

                foreach ($contacts as $contact) {
                    $oldStatus = $contact->status;
                    $contact->update(['status' => $statusTo]);

                    // Log as a system note
                    $contact->notes()->create([
                        'team_id' => $team->id,
                        'body' => "Configuration: System automatically changed status from {$oldStatus} to {$statusTo} (Status Rule).",
                        'type' => 'system'
                    ]);

                    $this->info("Team [{$team->name}]: Updated Contact [{$contact->name}] from {$oldStatus} to {$statusTo}");
                }
            }
        }

        return Command::SUCCESS;
    }
}
