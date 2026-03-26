<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Contact;
use App\Services\AutomationService;
use Carbon\Carbon;

class CheckAutomationTriggers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'automation:check-triggers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for time-based automation triggers like birthdays and inactivity';

    /**
     * Execute the console command.
     */
    public function handle(AutomationService $automationService)
    {
        $this->info("Starting time-based automation trigger check...");

        // 1. Birthday Trigger (T6)
        $this->checkBirthdayTriggers($automationService);

        // 2. Inactivity Trigger (T5)
        $this->checkInactivityTriggers($automationService);

        $this->info("Trigger check completed.");
    }

    protected function checkBirthdayTriggers(AutomationService $automationService)
    {
        $today = now()->format('m-d');
        
        // Find contacts whose birthday (stored in custom_attributes) is today
        // Assuming birthday is stored as 'YYYY-MM-DD' or 'MM-DD'
        $contacts = Contact::where('custom_attributes', 'LIKE', "%$today%")->get();

        foreach ($contacts as $contact) {
            $birthday = $contact->custom_attributes['birthday'] ?? null;
            if ($birthday && str_ends_with($birthday, $today)) {
                $this->line("Triggering birthday automation for contact #{$contact->id}");
                $automationService->checkSpecialTriggers($contact, 'contact_birthday', [
                    'birthday' => $birthday
                ]);
            }
        }
    }

    protected function checkInactivityTriggers(AutomationService $automationService)
    {
        // Find contacts who haven't messaged in X days
        // We can query contacts where last_interaction_at is older than N days 
        // and they don't have an active automation run.
        
        $inactivityDays = 7; // Default or could be dynamic
        $threshold = now()->subDays($inactivityDays);

        $contacts = Contact::where('last_interaction_at', '<', $threshold)
            ->whereNotNull('last_interaction_at')
            ->get();

        foreach ($contacts as $contact) {
            $this->line("Triggering inactivity automation for contact #{$contact->id}");
            $automationService->checkSpecialTriggers($contact, 'inactivity', [
                'last_interaction_at' => $contact->last_interaction_at->toDateTimeString(),
                'days_inactive' => $inactivityDays
            ]);
        }
    }
}
