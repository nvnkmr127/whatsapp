<?php

namespace App\Core\Workflow\Handlers;

use App\Core\Workflow\ActionHandlerInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackActionHandler implements ActionHandlerInterface
{
    public function handle(array $config, Model $subject, array $context): void
    {
        $webhookUrl = $config['webhook_url'] ?? get_setting('slack_webhook_url');
        $message = $config['message'] ?? 'Workflow Notification';

        if (! $webhookUrl) {
            return;
        }

        try {
            // Variable replacement
            $message = str_replace('{subject_id}', $subject->id ?? '', $message);
            $message = str_replace('{subject_name}', $subject->name ?? $subject->title ?? 'Record', $message);

            Http::post($webhookUrl, [
                'text' => $message,
            ]);
            Log::info('Workflow sent Slack notification.');
        } catch (\Exception $e) {
            Log::error('Workflow Slack Error: '.$e->getMessage());
        }
    }
}
