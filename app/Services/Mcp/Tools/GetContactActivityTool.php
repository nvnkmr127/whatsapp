<?php

namespace App\Services\Mcp\Tools;

use App\Models\Team;
use App\Services\Mcp\McpTool;

class GetContactActivityTool implements McpTool
{
    public function schema(): array
    {
        return [
            'name'        => 'get_contact_activity',
            'description' => 'Get the full activity timeline for a contact: tag changes, opt-in/out events, automation triggers, note additions, and other contact lifecycle events.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'phone' => ['type' => 'string', 'description' => 'Contact phone in E.164 format'],
                    'limit' => ['type' => 'integer', 'default' => 30, 'minimum' => 1, 'maximum' => 100],
                ],
                'required' => ['phone'],
            ],
        ];
    }

    public function handle(array $input, Team $team): array
    {
        $phone = \App\Helpers\PhoneNumberHelper::normalize($input['phone']);

        $contact = \App\Models\Contact::where('team_id', $team->id)
            ->where('phone_number', $phone)
            ->firstOrFail();

        $limit = min((int) ($input['limit'] ?? 30), 100);

        $events = \App\Models\ContactEvent::where('contact_id', $contact->id)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn($e) => [
                'id'         => $e->id,
                'type'       => $e->type ?? $e->event_type ?? null,
                'description'=> $e->description ?? $e->data ?? null,
                'created_at' => $e->created_at?->toDateTimeString(),
            ]);

        if ($events->isEmpty()) {
            return [['type' => 'text', 'text' => "No activity found for {$phone}."]];
        }

        return [['type' => 'text', 'text' => json_encode([
            'contact'   => ['name' => $contact->name, 'phone' => $phone],
            'total'     => $events->count(),
            'events'    => $events->values(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)]];
    }
}
