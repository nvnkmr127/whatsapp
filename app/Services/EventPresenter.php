<?php

namespace App\Services;

use App\Models\SystemEvent;
use Illuminate\Support\Str;

class EventPresenter
{
    public static function badgeClass(SystemEvent $event): string
    {
        $type = strtolower(class_basename($event->event_type));
        $category = strtolower($event->category ?? '');

        if ($category === 'error' || Str::contains($type, ['error', 'fail', 'exception', 'critical'])) {
            return 'border-rose-500/30 text-rose-600 bg-rose-50 dark:bg-rose-900/20';
        }

        if (Str::contains($type, ['warning', 'alert', 'limit'])) {
            return 'border-amber-500/30 text-amber-600 bg-amber-50 dark:bg-amber-900/20';
        }

        if (Str::contains($type, ['success', 'complete', 'sent', 'deliver', 'read'])) {
            return 'border-emerald-500/30 text-emerald-600 bg-emerald-50 dark:bg-emerald-900/20';
        }

        if (Str::contains($type, ['message', 'receive', 'chat'])) {
            return 'border-wa-teal/30 text-wa-teal bg-wa-teal/10';
        }

        return 'border-slate-500/30 text-slate-600 bg-slate-50 dark:bg-slate-800';
    }

    public static function summary(SystemEvent $event): string
    {
        $payload = is_array($event->payload) ? $event->payload : [];
        $type = class_basename($event->event_type);
        
        $summaryParts = [];
        
        if (isset($payload['message']) && is_string($payload['message'])) {
            $summaryParts[] = Str::limit($payload['message'], 60);
        } elseif (isset($payload['error']) && is_string($payload['error'])) {
            $summaryParts[] = Str::limit($payload['error'], 60);
        } elseif (isset($payload['status']) && is_string($payload['status'])) {
            $summaryParts[] = "Status: " . $payload['status'];
        }
        
        if (isset($payload['phone']) && is_string($payload['phone'])) {
            $summaryParts[] = $payload['phone'];
        }
        
        if (empty($summaryParts)) {
            return trim(preg_replace('/(?<!^)[A-Z]/', ' $0', $type));
        }
        
        return implode(' - ', $summaryParts);
    }
}
