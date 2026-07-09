<?php

namespace App\Services\Mcp\Tools;

use App\Models\Contact;
use App\Models\Team;
use App\Services\Mcp\McpTool;

class GetContactTool implements McpTool
{
    public function schema(): array
    {
        return [
            'name'        => 'get_contact',
            'description' => 'Retrieve contact details by phone number.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'phone' => ['type' => 'string', 'description' => 'Phone number in E.164 format'],
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

        $data = [
            'id'           => $contact->id,
            'name'         => $contact->name,
            'phone_number' => $contact->phone_number,
            'email'        => $contact->email,
            'tags'         => $contact->tags()->pluck('name'),
            'created_at'   => $contact->created_at?->toDateTimeString(),
        ];

        return [['type' => 'text', 'text' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)]];
    }
}
