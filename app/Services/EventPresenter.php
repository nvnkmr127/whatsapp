<?php

namespace App\Services;

use App\Models\SystemEvent;
use Illuminate\Support\Str;

class EventPresenter
{
    /**
     * Generate a human-readable summary of the event.
     */
    public static function summary(SystemEvent $event): string
    {
        $payload = $event->payload;
        $type = $event->event_type;

        // 1. Customized Summaries based on Event Type
        if (str_contains($type, 'Order')) {
            $id = $payload['order_id'] ?? $payload['id'] ?? 'Unknown';
            $amount = isset($payload['total_amount']) ? ($payload['currency'] ?? '') . ' ' . $payload['total_amount'] : '';
            $status = $payload['status'] ?? '';

            return "Order #{$id} {$status} {$amount}";
        }

        if (str_contains($type, 'Message')) {
            $content = $payload['content'] ?? $payload['message']['content'] ?? '';
            return 'Message: "' . Str::limit($content, 30) . '"';
        }

        if (str_contains($type, 'Contact')) {
            $id = $payload['contact_id'] ?? 'Unknown';
            $state = $payload['new_state'] ?? $payload['lifecycle_state'] ?? '';
            return "Contact #{$id} -> {$state}";
        }

        // 2. Fallback: Generic Key-Value
        if (empty($payload)) {
            return 'No details';
        }

        // Flatten first level for display
        $summary = [];
        foreach ($payload as $key => $value) {
            if (is_array($value))
                continue;
            if ($key === 'id')
                continue;
            $summary[] = "$key: " . Str::limit((string) $value, 20);
            if (count($summary) >= 3)
                break;
        }

        return implode(', ', $summary);
    }

    /**
     * Get a color class for the badge based on category/severity.
     */
    public static function badgeClass(SystemEvent $event): string
    {
        if ($event->category === 'business') {
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        }
        if ($event->category === 'operational') {
            // Check for error keywords in type or payload
            if (str_contains(strtolower($event->event_type), 'fail') || str_contains(strtolower($event->event_type), 'error')) {
                return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
            }
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
        }
        return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
}
