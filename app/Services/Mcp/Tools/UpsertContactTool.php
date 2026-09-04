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
                    'phone'             => ['type' => 'string', 'description' => 'Phone number in E.164 format (required)'],
                    'name'              => ['type' => 'string', 'description' => 'Full name'],
                    'email'             => ['type' => 'string', 'description' => 'Email address'],
                    'company'           => ['type' => 'string', 'description' => 'Company name'],
                    'custom_attributes' => ['type' => 'object', 'description' => 'Optional key-value pairs of custom attributes'],
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
            'name'  => $input['name'] ?? null,
            'email' => $input['email'] ?? null,
        ], fn($v) => $v !== null);

        $customAttributes = is_array($contact?->custom_attributes) ? $contact->custom_attributes : [];
        if (! empty($input['custom_attributes']) && is_array($input['custom_attributes'])) {
            $customAttributes = array_merge($customAttributes, $input['custom_attributes']);
        }
        if (! empty($input['company'])) {
            $customAttributes['company'] = trim($input['company']);
        }
        if (! empty($customAttributes)) {
            $data['custom_attributes'] = $customAttributes;
        }

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
