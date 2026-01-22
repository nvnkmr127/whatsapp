<?php

namespace App\Console\Commands;

use App\Models\Contact;
use App\Models\Message;
use App\Services\ContactStateManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReconcileContactStates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contacts:reconcile-states 
                            {--team= : Team ID to reconcile}
                            {--limit= : Limit number of contacts to process}
                            {--fix : Automatically fix discrepancies}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcile contact state fields with source of truth';

    /**
     * Execute the console command.
     */
    public function handle(ContactStateManager $stateManager)
    {
        $teamId = $this->option('team');
        $limit = $this->option('limit');
        $autoFix = $this->option('fix');

        $query = Contact::query();

        if ($teamId) {
            $query->where('team_id', $teamId);
            $this->info("Reconciling team #{$teamId}");
        } else {
            $this->info("Reconciling all teams");
        }

        if ($limit) {
            $query->limit((int) $limit);
        }

        $contacts = $query->get();
        $this->info("Processing {$contacts->count()} contacts...");

        $bar = $this->output->createProgressBar($contacts->count());
        $bar->start();

        $stats = [
            'processed' => 0,
            'discrepancies' => 0,
            'fixed' => 0,
        ];

        foreach ($contacts as $contact) {
            $discrepancies = $this->reconcileContact($contact, $stateManager, $autoFix);

            if (!empty($discrepancies)) {
                $stats['discrepancies']++;

                if ($autoFix) {
                    $stats['fixed']++;
                }
            }

            $stats['processed']++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Display summary
        $this->info('=== Reconciliation Summary ===');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Contacts Processed', $stats['processed']],
                ['Discrepancies Found', $stats['discrepancies']],
                ['Fixed', $stats['fixed']],
            ]
        );

        if ($stats['discrepancies'] > 0 && !$autoFix) {
            $this->newLine();
            $this->warn("Run with --fix to automatically correct discrepancies");
        }

        return 0;
    }

    /**
     * Reconcile a single contact.
     */
    protected function reconcileContact(Contact $contact, ContactStateManager $stateManager, bool $autoFix): array
    {
        $discrepancies = [];

        // Recalculate from source of truth
        $actualMessageCount = Message::where('contact_id', $contact->id)->count();
        $actualInboundCount = Message::where('contact_id', $contact->id)
            ->where('direction', 'inbound')
            ->count();
        $actualOutboundCount = Message::where('contact_id', $contact->id)
            ->where('direction', 'outbound')
            ->count();

        // Check message count
        if ($contact->message_count !== $actualMessageCount) {
            $discrepancies[] = "message_count: {$contact->message_count} → {$actualMessageCount}";

            if ($autoFix) {
                $contact->update(['message_count' => $actualMessageCount]);
            }
        }

        // Check inbound count
        if ($contact->inbound_message_count !== $actualInboundCount) {
            $discrepancies[] = "inbound_message_count: {$contact->inbound_message_count} → {$actualInboundCount}";

            if ($autoFix) {
                $contact->update(['inbound_message_count' => $actualInboundCount]);
            }
        }

        // Check outbound count
        if ($contact->outbound_message_count !== $actualOutboundCount) {
            $discrepancies[] = "outbound_message_count: {$contact->outbound_message_count} → {$actualOutboundCount}";

            if ($autoFix) {
                $contact->update(['outbound_message_count' => $actualOutboundCount]);
            }
        }

        // Recalculate derived fields
        if ($autoFix) {
            $stateManager->updateDerivedFields($contact);
        }

        if (!empty($discrepancies)) {
            Log::warning("Contact state discrepancies", [
                'contact_id' => $contact->id,
                'discrepancies' => $discrepancies,
                'fixed' => $autoFix,
            ]);
        }

        return $discrepancies;
    }
}
