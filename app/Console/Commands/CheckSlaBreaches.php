<?php

namespace App\Console\Commands;

use App\Models\Contact;
use Illuminate\Console\Command;

class CheckSlaBreaches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sla:check {hours=2}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for unanswered messages exceeding the SLA time limit';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hours = $this->argument('hours');
        $threshold = now()->subHours($hours);

        $contacts = Contact::where('has_pending_reply', true)
            ->where('last_customer_message_at', '<', $threshold)
            ->whereNull('sla_breached_at')
            ->get();

        foreach ($contacts as $contact) {
            // Mark as breached
            $contact->update(['sla_breached_at' => now()]);

            // Add internal note
            $contact->notes()->create([
                'team_id' => $contact->team_id,
                'user_id' => $contact->assigned_to ?? $contact->team->user_id, // Assigned agent or Team Owner
                'body' => "⚠️ SLA ALERT: Customer has been waiting for more than {$hours} hours.",
                'type' => 'system'
            ]);

            $this->info("Flagged Contact ID: {$contact->id}");
        }

        $this->info('SLA Check Complete.');
    }
}
