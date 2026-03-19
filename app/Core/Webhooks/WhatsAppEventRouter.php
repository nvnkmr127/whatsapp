<?php

namespace App\Core\Webhooks;

use App\Models\Team;
use App\Models\WhatsappTemplate;
use App\Services\EventBusService;
use App\Services\WhatsAppCallProcessor;
use App\Services\CallPermissionService;
use Illuminate\Support\Facades\Log;

class WhatsAppEventRouter
{
    protected EventBusService $eventBus;
    protected ?int $teamId;
    protected ?string $phoneId;

    public function __construct(EventBusService $eventBus, ?int $teamId, ?string $phoneId)
    {
        $this->eventBus = $eventBus;
        $this->teamId = $teamId;
        $this->phoneId = $phoneId;
    }

    /**
     * Route the change event to specific handlers.
     */
    public function route(array $change, array $fullPayload): void
    {
        // 1. Handle Messages (Inbound)
        if (isset($change['messages']) && is_array($change['messages'])) {
            $this->handleInboundMessages($change['messages'], $fullPayload);
        }

        // 2. Handle Status Updates
        if (isset($change['statuses']) && is_array($change['statuses'])) {
            $this->handleStatusUpdates($change['statuses']);
        }

        // 3. Handle Template Status Updates
        if (($change['field'] ?? '') === 'message_template_status_update') {
            $this->handleTemplateStatusUpdate($change);
        }

        // 4. Handle Account Quality Updates
        if (($change['field'] ?? '') === 'phone_number_quality_update') {
            $this->handleQualityUpdate($change);
        }

        // 5. Handle Calls
        if (isset($change['calls']) && is_array($change['calls'])) {
            $this->handleCalls($change['calls']);
        }

        // 6. Handle Account Settings/Restrictions
        if (in_array($change['field'] ?? '', ['account_settings_update', 'account_update'])) {
            $this->handleAccountLifecycle($change);
        }

        // 7. Handle Interactive Responses
        if (isset($change['messages']) && is_array($change['messages'])) {
            foreach ($change['messages'] as $messageData) {
                if (($messageData['type'] ?? '') === 'interactive') {
                    $this->handleInteractiveResponse($messageData);
                }
            }
        }
    }

    protected function handleInboundMessages(array $messages, array $fullPayload): void
    {
        foreach ($messages as $messageData) {
            $wamid = $messageData['id'] ?? null;
            if (!$wamid) continue;

            $lockKey = "webhook_lock:{$wamid}";
            $lock = \Illuminate\Support\Facades\Cache::lock($lockKey, 10);

            if (!$lock->get()) {
                Log::info("Router: Parallel execution detected for {$wamid}. Skipping.");
                continue;
            }

            try {
                if (\App\Models\Message::where('whatsapp_message_id', $wamid)->exists()) {
                    continue;
                }

                $event = \App\Factories\EventFactory::makeInboundMessage($fullPayload);
                \App\Jobs\PersistMessageJob::dispatch($event['payload'], \App\Services\TraceContext::getTraceId())
                    ->onQueue('messages');

                $this->eventBus->publish('whatsapp_events', 'message.inbound', $event['payload'], $this->teamId);
            } finally {
                // Lock released by timeout or GC
            }
        }
    }

    protected function handleStatusUpdates(array $statuses): void
    {
        foreach ($statuses as $statusData) {
            $payload = [
                'provider_message_id' => $statusData['id'],
                'status' => $statusData['status'],
                'timestamp' => $statusData['timestamp'] ?? time(),
                'details' => $statusData
            ];

            \App\Jobs\UpdateMessageStatusJob::dispatch($payload, \App\Services\TraceContext::getTraceId())
                ->onQueue('messages');

            $this->eventBus->publish('whatsapp_events', 'message.status', $payload, $this->teamId);
        }
    }

    protected function handleTemplateStatusUpdate(array $change): void
    {
        $tId = $change['message_template_id'] ?? null;
        $newStatus = $change['event'] ?? null;

        if ($tId && $newStatus) {
            $tpl = WhatsappTemplate::where('whatsapp_template_id', $tId)->first();
            if ($tpl) {
                $tplRanks = ['APPROVED' => 1, 'PAUSED' => 2, 'FLAGGED' => 3, 'REJECTED' => 4, 'DISABLED' => 5];
                $oldRank = $tplRanks[$tpl->status] ?? 0;
                $newRank = $tplRanks[$newStatus] ?? 0;

                if ($newRank >= $oldRank || $tpl->status === 'APPROVED') {
                    $tpl->update(['status' => $newStatus]);
                }

                if (in_array($newStatus, ['FLAGGED', 'DISABLED'])) {
                    Log::critical("Template {$tpl->name} ({$tId}) is {$newStatus}.");
                }
            }
        }
    }

    protected function handleQualityUpdate(array $change): void
    {
        $status = $change['new_status'] ?? 'UNKNOWN';
        $quality = $change['new_quality_score'] ?? 'UNKNOWN';
        
        Log::info("WhatsApp Quality Update: Status={$status}, Quality={$quality}");

        if (in_array($status, ['FLAGGED', 'RESTRICTED']) || $quality === 'RED') {
            Log::critical("CRITICAL: WhatsApp Account Risk! Status: {$status}.");
            \App\Events\WhatsAppAccountRisk::dispatch('QUALITY_UPDATE', $change, $this->teamId);
        }
    }

    protected function handleCalls(array $calls): void
    {
        if (!$this->teamId) return;

        $team = Team::find($this->teamId);
        if ($team) {
            (new WhatsAppCallProcessor())->process($team, $calls);
        }
    }

    protected function handleAccountLifecycle(array $change): void
    {
        if (!$this->teamId || !$this->phoneId) return;

        $team = Team::find($this->teamId);
        if (!$team) return;

        if (($change['field'] ?? '') === 'account_settings_update') {
            $this->updateLocalCallSettings($team, $this->phoneId, $change);
        } else {
            $this->applyEnforcementActions($team, $this->phoneId, $change);
        }

        \App\Events\WhatsAppAccountUpdated::dispatch($change);
    }

    protected function updateLocalCallSettings(Team $team, string $phoneId, array $change): void
    {
        $settings = \App\Models\CallSettings::where('team_id', $team->id)
            ->where('phone_number_id', $phoneId)
            ->first();

        if ($settings && isset($change['calling'])) {
            $callingData = $change['calling'];
            $updates = [];
            if (isset($callingData['status'])) {
                $updates['calling_enabled'] = strtoupper($callingData['status']) === 'ENABLED';
            }
            if (isset($callingData['call_icon_visibility'])) {
                $updates['call_icon_visibility'] = strtoupper($callingData['call_icon_visibility']) === 'DEFAULT' ? 'show' : 'hide';
            }
            if (isset($callingData['callback_permission_status'])) {
                $updates['callback_permission_enabled'] = strtoupper($callingData['callback_permission_status']) === 'ENABLED';
            }
            if (!empty($updates)) $settings->update($updates);
        }
    }

    protected function applyEnforcementActions(Team $team, string $phoneId, array $change): void
    {
        $settings = \App\Models\CallSettings::where('team_id', $team->id)
            ->where('phone_number_id', $phoneId)
            ->first();

        if ($settings && isset($change['enforcement_action'])) {
            $action = $change['enforcement_action'];
            $reason = $change['enforcement_reason'] ?? 'Unknown';

            if (in_array($action, ['CALLING_RESTRICTED', 'CALLING_DISABLED'])) {
                $settings->applyRestriction($reason);
                \App\Events\CallRestrictionApplied::dispatch($team, $settings, $reason);
            }
        }
    }

    protected function handleInteractiveResponse(array $messageData): void
    {
        if (!$this->teamId) return;

        $buttonReply = $messageData['interactive']['button_reply'] ?? null;
        if (!$buttonReply) return;

        $payload = json_decode($buttonReply['payload'] ?? '{}', true);
        if (($payload['action'] ?? '') === 'grant_call_permission') {
            $permissionId = $payload['permission_id'] ?? null;
            if ($permissionId) {
                $permission = \App\Models\CallPermission::find($permissionId);
                if ($permission && $permission->permission_status === 'requested') {
                    (new \App\Services\CallPermissionService())->grantPermission($permission);
                }
            }
        }
    }
}
