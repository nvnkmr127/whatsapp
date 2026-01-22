<?php

namespace App\Console\Commands;

use App\Models\Contact;
use App\Models\Team;
use App\Services\ContactDeduplicationService;
use Illuminate\Console\Command;

class DetectDuplicatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contacts:detect-duplicates 
                            {--team= : Team ID to scan}
                            {--auto-merge : Automatically merge high-confidence duplicates}
                            {--threshold=80 : Confidence threshold for auto-merge (0-100)}
                            {--limit= : Limit number of contacts to scan}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detect and optionally merge duplicate contacts';

    /**
     * Execute the console command.
     */
    public function handle(ContactDeduplicationService $dedup)
    {
        $teamId = $this->option('team');
        $autoMerge = $this->option('auto-merge');
        $threshold = (float) $this->option('threshold');
        $limit = $this->option('limit');

        // Validate threshold
        if ($threshold < 0 || $threshold > 100) {
            $this->error('Threshold must be between 0 and 100');
            return 1;
        }

        // Get contacts to scan
        $query = Contact::query();

        if ($teamId) {
            $team = Team::find($teamId);
            if (!$team) {
                $this->error("Team #{$teamId} not found");
                return 1;
            }
            $query->where('team_id', $teamId);
            $this->info("Scanning team: {$team->name}");
        } else {
            $this->info("Scanning all teams");
        }

        if ($limit) {
            $query->limit((int) $limit);
        }

        $contacts = $query->get();
        $this->info("Found {$contacts->count()} contacts to scan");

        if ($contacts->isEmpty()) {
            $this->info('No contacts to scan');
            return 0;
        }

        // Confirm auto-merge
        if ($autoMerge) {
            if (!$this->confirm("Auto-merge enabled with threshold {$threshold}%. Continue?")) {
                $this->info('Aborted');
                return 0;
            }
        }

        $bar = $this->output->createProgressBar($contacts->count());
        $bar->start();

        $stats = [
            'scanned' => 0,
            'duplicates_found' => 0,
            'auto_merged' => 0,
            'queued_for_review' => 0,
            'errors' => 0,
        ];

        $processedPairs = [];

        foreach ($contacts as $contact) {
            try {
                $duplicates = $dedup->findDuplicates($contact, $threshold);

                foreach ($duplicates as $duplicateData) {
                    $duplicate = $duplicateData['contact'];
                    $score = $duplicateData['score'];

                    // Skip if already processed this pair
                    $pairKey = $this->getPairKey($contact->id, $duplicate->id);
                    if (isset($processedPairs[$pairKey])) {
                        continue;
                    }
                    $processedPairs[$pairKey] = true;

                    $stats['duplicates_found']++;

                    if ($autoMerge && $score['score'] >= $threshold) {
                        // Auto-merge
                        $dedup->mergeContacts($contact, $duplicate, [
                            'strategy' => 'auto',
                            'confidence_score' => $score['score'],
                        ]);

                        $stats['auto_merged']++;

                        $this->newLine();
                        $this->line(sprintf(
                            '<info>✓ Auto-merged:</info> %s (#%d) + %s (#%d) [Score: %.0f%%]',
                            $contact->name ?? $contact->phone_number,
                            $contact->id,
                            $duplicate->name ?? $duplicate->phone_number,
                            $duplicate->id,
                            $score['score']
                        ));
                    } else {
                        // Queue for manual review
                        $dedup->queueForReview($contact, $duplicate, $score);
                        $stats['queued_for_review']++;
                    }
                }

                $stats['scanned']++;
            } catch (\Exception $e) {
                $stats['errors']++;
                $this->newLine();
                $this->error("Error processing contact #{$contact->id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Display summary
        $this->info('=== Duplicate Detection Summary ===');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Contacts Scanned', $stats['scanned']],
                ['Duplicates Found', $stats['duplicates_found']],
                ['Auto-Merged', $stats['auto_merged']],
                ['Queued for Review', $stats['queued_for_review']],
                ['Errors', $stats['errors']],
            ]
        );

        if ($stats['queued_for_review'] > 0) {
            $this->newLine();
            $this->info("Review pending duplicates in the admin panel or run:");
            $this->line("  php artisan contacts:review-duplicates");
        }

        return 0;
    }

    /**
     * Get unique key for contact pair.
     */
    protected function getPairKey(int $id1, int $id2): string
    {
        return min($id1, $id2) . '-' . max($id1, $id2);
    }
}
