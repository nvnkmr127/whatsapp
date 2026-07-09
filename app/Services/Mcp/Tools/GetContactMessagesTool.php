<?php

namespace App\Services\Mcp\Tools;

use App\Models\Contact;
use App\Models\Message;
use App\Models\Team;
use App\Services\Mcp\McpTool;

class GetContactMessagesTool implements McpTool
{
    public function schema(): array
    {
        return [
            'name'        => 'get_contact_messages',
            'description' => 'Retrieve messages sent to or received from a specific contact, with full delivery status (sent, delivered, read) and timestamps. Supports filtering by date/time range and direction.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'phone'      => ['type' => 'string', 'description' => 'Contact phone number in E.164 format'],
                    'from'       => ['type' => 'string', 'description' => 'Start datetime (ISO 8601 or Y-m-d H:i:s). E.g. "2025-07-09 10:00:00"'],
                    'to'         => ['type' => 'string', 'description' => 'End datetime (ISO 8601 or Y-m-d H:i:s). E.g. "2025-07-09 18:00:00"'],
                    'direction'  => [
                        'type'        => 'string',
                        'enum'        => ['inbound', 'outbound', 'both'],
                        'default'     => 'both',
                        'description' => 'Filter by message direction',
                    ],
                    'limit'      => ['type' => 'integer', 'default' => 50, 'minimum' => 1, 'maximum' => 200],
                ],
                'required' => ['phone'],
            ],
        ];
    }

    public function handle(array $input, Team $team): array
    {
        $phone = \App\Helpers\PhoneNumberHelper::normalize($input['phone']);

        $contact = Contact::where('team_id', $team->id)
            ->where('phone_number', $phone)
            ->first();

        if (! $contact) {
            return [['type' => 'text', 'text' => "No contact found for {$phone}."]];
        }

        $limit = min((int) ($input['limit'] ?? 50), 200);

        $query = Message::where('team_id', $team->id)
            ->where('contact_id', $contact->id)
            ->orderBy('created_at', 'asc');

        // Time range filter
        if (! empty($input['from'])) {
            $query->where('created_at', '>=', \Carbon\Carbon::parse($input['from']));
        }
        if (! empty($input['to'])) {
            $query->where('created_at', '<=', \Carbon\Carbon::parse($input['to']));
        }

        // Direction filter
        $direction = $input['direction'] ?? 'both';
        if ($direction !== 'both') {
            $query->where('direction', $direction);
        }

        $messages = $query->limit($limit)->get()->map(fn($m) => [
            'id'              => $m->id,
            'direction'       => $m->direction,            // inbound | outbound
            'type'            => $m->type,                 // text | template | image | ...
            'content'         => $m->content,
            'status'          => $m->status,               // queued | sent | delivered | read | failed

            // Delivery timeline
            'sent_at'         => $m->sent_at?->toDateTimeString(),
            'delivered_at'    => $m->delivered_at?->toDateTimeString(),
            'read_at'         => $m->read_at?->toDateTimeString(),
            'created_at'      => $m->created_at?->toDateTimeString(),

            // Reply chain
            'is_reply'        => ! is_null($m->reply_to_message_id),
            'reply_to_id'     => $m->reply_to_message_id,

            // Media
            'media_type'      => $m->media_type,
            'media_url'       => $m->full_media_url,

            // Error (if any)
            'error'           => $m->error_message,

            // Source (bot, automation, campaign, manual)
            'source'          => $this->resolveSource($m),
        ]);

        if ($messages->isEmpty()) {
            $rangeNote = '';
            if (! empty($input['from']) || ! empty($input['to'])) {
                $rangeNote = ' in the specified time range';
            }
            return [['type' => 'text', 'text' => "No messages found for {$phone}{$rangeNote}."]];
        }

        $summary = [
            'contact'       => ['name' => $contact->name, 'phone' => $phone],
            'total'         => $messages->count(),
            'outbound'      => $messages->where('direction', 'outbound')->count(),
            'inbound'       => $messages->where('direction', 'inbound')->count(),
            'read_count'    => $messages->whereNotNull('read_at')->count(),
            'failed_count'  => $messages->where('status', 'failed')->count(),
            'messages'      => $messages->values(),
        ];

        return [['type' => 'text', 'text' => json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)]];
    }

    private function resolveSource(Message $m): string
    {
        if ($m->direction === 'inbound') {
            return 'contact';
        }
        if (! empty($m->metadata['is_bot'])) {
            return 'bot';
        }
        if ($m->automation_id) {
            return 'automation';
        }
        if ($m->campaign_id) {
            return 'campaign';
        }
        return 'agent';
    }
}
