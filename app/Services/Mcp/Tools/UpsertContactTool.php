<?php

namespace App\Services\Mcp\Tools;

use App\Models\Contact;
use App\Models\Team;
use App\Services\Mcp\McpTool;

class UpsertContactTool implements McpTool
{
    public function schema(): array
    {
        return [
            'name'        => 'upsert_contact',
            'description' => 'Create or update a contact by phone number. Safe to call multiple times.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'phone'   => ['type' => 'string', 'description' => 'Phone number in E.164 format (required)'],
                    'name'    => ['type' => 'string', 'description' => 'Full name'],
                    'email'   => ['type' => 'string', 'description' => 'Email address'],
                    'company' => ['type' => 'string', 'description' => 'Company name'],
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

        $data = array_filter([
            'name'    => $input['name'] ?? null,
            'email'   => $input['email'] ?? null,
            'company' => $input['company'] ?? null,
        ], fn($v) => $v !== null);

        if ($contact) {
            $contact->update($data);
            $verb = 'updated';
        } else {
            $contact = Contact::create(array_merge($data, [
                'team_id'      => $team->id,
                'phone_number' => $phone,
            ]));
            $verb = 'created';
        }

        return [['type' => 'text', 'text' => "Contact {$verb}. ID: {$contact->id}, Phone: {$phone}"]];
    }
}
