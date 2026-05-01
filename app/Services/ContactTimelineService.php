<?php

namespace App\Services;

use App\Models\Contact;
use Illuminate\Support\Collection;

class ContactTimelineService
{
    /**
     * Build a unified timeline for a contact.
     */
    public function getTimeline(Contact $contact, bool $excludeSuperAdmin = true): Collection
    {
        $timeline = collect();

        // 1. Messages
        $contact->messages()->latest()->take(50)->get()->each(function ($message) use ($timeline) {
            $timeline->push([
                'type' => 'message',
                'id' => 'msg-'.$message->id,
                'title' => $message->direction === 'inbound' ? 'Message Received' : 'Message Sent',
                'description' => $message->content,
                'occurred_at' => $message->sent_at ?? $message->created_at,
                'metadata' => [
                    'direction' => $message->direction,
                    'status' => $message->status,
                    'type' => $message->type,
                ],
            ]);
        });

        // 2. Notes
        $contact->notes()->with('user')->latest()->take(50)->get()->each(function ($note) use ($timeline, $excludeSuperAdmin) {
            if ($excludeSuperAdmin && $note->user?->is_super_admin) {
                return;
            }
            $timeline->push([
                'type' => 'note',
                'id' => 'note-'.$note->id,
                'title' => 'Note Added',
                'description' => $note->content,
                'occurred_at' => $note->created_at,
                'user' => $note->user?->name,
            ]);
        });

        // 3. Contact Events
        $contact->contactEvents()->latest()->take(50)->get()->each(function ($event) use ($timeline) {
            $timeline->push([
                'type' => 'event',
                'id' => 'event-'.$event->id,
                'title' => str_replace('_', ' ', ucfirst($event->event_type)),
                'description' => $event->event_data['description'] ?? '',
                'occurred_at' => $event->occurred_at ?? $event->created_at,
                'metadata' => $event->event_data,
            ]);
        });

        // 4. CRM Activities
        $contact->crmActivities()->with('user')->latest()->take(50)->get()->each(function ($activity) use ($timeline, $excludeSuperAdmin) {
            if ($excludeSuperAdmin && $activity->user?->is_super_admin) {
                return;
            }
            $timeline->push([
                'type' => 'crm_activity',
                'id' => 'crm-'.$activity->id,
                'title' => str_replace('_', ' ', ucfirst($activity->activity_type)),
                'description' => $activity->description,
                'occurred_at' => $activity->performed_at ?? $activity->created_at,
                'user' => $activity->user?->name,
                'metadata' => $activity->metadata,
            ]);
        });

        // 5. Orders
        $contact->orders()->latest()->take(50)->get()->each(function ($order) use ($timeline) {
            $timeline->push([
                'type' => 'order',
                'id' => 'order-'.$order->id,
                'title' => 'Order Placed: #'.($order->order_id ?? $order->id),
                'description' => "Amount: {$order->total_amount} {$order->currency} - Status: ".ucfirst($order->status),
                'occurred_at' => $order->created_at,
                'metadata' => $order->toArray(),
            ]);
        });

        // 6. Deals
        $contact->deals()->latest()->take(50)->get()->each(function ($deal) use ($timeline) {
            $timeline->push([
                'type' => 'deal',
                'id' => 'deal-'.$deal->id,
                'title' => 'Deal Created: '.$deal->title,
                'description' => "Value: {$deal->value} {$deal->currency} - Status: ".ucfirst($deal->status),
                'occurred_at' => $deal->created_at,
                'metadata' => $deal->toArray(),
            ]);
        });

        // 7. Workflow Logs
        $contact->workflowLogs()->with('workflow')->latest()->take(50)->get()->each(function ($log) use ($timeline) {
            $timeline->push([
                'type' => 'automation',
                'id' => 'auto-'.$log->id,
                'title' => 'Automation Triggered: '.($log->workflow->name ?? 'Workflow'),
                'description' => 'Status: '.ucfirst($log->status),
                'occurred_at' => $log->started_at ?? $log->created_at,
                'metadata' => $log->execution_data,
            ]);
        });

        // 8. Activity Logs
        $contact->activityLogs()->with('user')->latest()->take(30)->get()->each(function ($log) use ($timeline, $excludeSuperAdmin) {
            if ($excludeSuperAdmin && $log->user?->is_super_admin) {
                return;
            }
            $timeline->push([
                'type' => 'activity_log',
                'id' => 'act-'.$log->id,
                'title' => 'Action Logged',
                'description' => $log->description ?? 'System activity occurred',
                'occurred_at' => $log->created_at,
                'user' => $log->user?->name,
                'metadata' => $log->properties,
            ]);
        });

        return $timeline->sortByDesc('occurred_at')->values();
    }

    /**
     * Get media items for a contact.
     */
    public function getMediaVault(Contact $contact): Collection
    {
        return $contact->messages()
            ->whereNotNull('media_url')
            ->orderByDesc('created_at')
            ->get(['id', 'type', 'media_url', 'media_type', 'caption', 'created_at']);
    }

    /**
     * Get interaction heatmap data.
     */
    public function getInteractionHeatmap(Contact $contact): array
    {
        $isSqlite = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite';

        $selectRaw = $isSqlite
            ? "strftime('%w', created_at) + 1 as day, strftime('%H', created_at) as hour, COUNT(*) as count"
            : 'DAYOFWEEK(created_at) as day, HOUR(created_at) as hour, COUNT(*) as count';

        $interactions = $contact->messages()
            ->selectRaw($selectRaw)
            ->groupBy('day', 'hour')
            ->get();

        $heatmap = [];
        for ($d = 1; $d <= 7; $d++) {
            for ($h = 0; $h < 24; $h++) {
                $heatmap[$d][$h] = 0;
            }
        }

        foreach ($interactions as $interaction) {
            $heatmap[$interaction->day][$interaction->hour] = $interaction->count;
        }

        return $heatmap;
    }
}
