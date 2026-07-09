<?php

namespace App\Services\Mcp\Tools;

use App\Models\Team;
use App\Services\Mcp\McpTool;

class TagContactTool implements McpTool
{
    public function schema(): array
    {
        return [
            'name'        => 'tag_contact',
            'description' => 'Add or remove a tag from a contact. Use get_contact_tags to find valid tag IDs.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'phone'  => ['type' => 'string', 'description' => 'Contact phone in E.164 format'],
                    'tag_id' => ['type' => 'integer', 'description' => 'Tag ID to apply'],
                    'action' => ['type' => 'string', 'enum' => ['add', 'remove'], 'default' => 'add'],
                ],
                'required' => ['phone', 'tag_id'],
            ],
        ];
    }

    public function handle(array $input, Team $team): array
    {
        $phone = \App\Helpers\PhoneNumberHelper::normalize($input['phone']);

        $contact = \App\Models\Contact::where('team_id', $team->id)
            ->where('phone_number', $phone)
            ->firstOrFail();

        // Validate the tag belongs to this team
        $tag = \App\Models\ContactTag::where('id', $input['tag_id'])
            ->where('team_id', $team->id)
            ->firstOrFail();

        $action = $input['action'] ?? 'add';

        if ($action === 'remove') {
            $contact->tags()->detach($tag->id);
            $verb = 'removed from';
        } else {
            $contact->tags()->syncWithoutDetaching([$tag->id]);
            $verb = 'added to';
        }

        return [['type' => 'text', 'text' => "Tag \"{$tag->name}\" {$verb} contact {$phone}."]];
    }
}
