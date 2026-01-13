<?php

namespace App\Console\Commands;

use App\Models\Chat;
use App\Models\ChatMessage;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearChatHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:clear-chat-history';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear old chat history based on configured settings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $settings = get_settings_by_group('whats-mark');

        // update last cron run value in db
        set_setting('cron-job.last_cron_run', now()->timestamp);

        // Check if auto clear feature is enabled
        if (! $settings || ! $settings->enable_auto_clear_chat || ! $settings->auto_clear_history_time) {
            $this->info('Auto clear chat history is disabled or not configured properly.');

            return;
        }

        $this->info('Starting chat history cleanup process...');

        $daysToKeep = (int) $settings->auto_clear_history_time;
        $cutoffDate = Carbon::now()->subDays($daysToKeep);

        try {
            DB::beginTransaction();

            // Find messages older than the cutoff date
            $oldMessages = ChatMessage::where('time_sent', '<', $cutoffDate->format('Y-m-d H:i:s'))->get();

            $messageCount = $oldMessages->count();
            $this->info("Found {$messageCount} messages older than {$daysToKeep} days.");

            if ($messageCount > 0) {
                // Get interaction IDs to potentially delete empty chats later
                $affectedInteractionIds = $oldMessages->pluck('interaction_id')->unique()->toArray();

                // Delete old messages
                ChatMessage::where('time_sent', '<', $cutoffDate->format('Y-m-d H:i:s'))->delete();
                $this->info("Deleted {$messageCount} old messages.");

                // Clean up empty chats (those with no messages left)
                $emptyInteractionsCount = 0;
                foreach ($affectedInteractionIds as $interactionId) {
                    $remainingMessages = ChatMessage::where('interaction_id', $interactionId)->count();

                    if ($remainingMessages === 0) {
                        Chat::where('id', $interactionId)->delete();
                        $emptyInteractionsCount++;
                    }
                }

                $this->info("Deleted {$emptyInteractionsCount} empty chat conversations.");
                $this->info('Chat history cleanup completed successfully.');

                whatsapp_log(
                    "Auto chat history cleanup: Deleted {$messageCount} messages and {$emptyInteractionsCount} empty chats older than {$daysToKeep} days.",
                    'info',
                    [
                        'message_count'            => $messageCount,
                        'empty_interactions_count' => $emptyInteractionsCount,
                        'days_to_keep'             => $daysToKeep,
                    ]
                );
            } else {
                $this->info('No old messages to delete.');
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error during chat history cleanup: ' . $e->getMessage());
            whatsapp_log(
                'Chat history cleanup failed: ' . $e->getMessage(),
                'error',
                [
                    'exception' => $e,
                ]
            );
        }
    }
}
