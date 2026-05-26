<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AutoLoginSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Super Admin
        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'is_super_admin' => true,
            ]
        );

        if (! $superAdmin->ownedTeams()->where('personal_team', true)->exists()) {
            $superAdmin->ownedTeams()->save(Team::forceCreate([
                'user_id' => $superAdmin->id,
                'name' => "Admin's Team",
                'personal_team' => true,
            ]));
        }

        $adminTeam = $superAdmin->ownedTeams()->where('personal_team', true)->first();
        $superAdmin->current_team_id = $adminTeam->id;
        $superAdmin->save();

        // 2. Manager
        $manager = User::updateOrCreate(
            ['email' => 'manager@example.com'],
            [
                'name' => 'Team Manager',
                'password' => Hash::make('password'),
                'is_super_admin' => false,
            ]
        );

        if (! $manager->ownedTeams()->where('personal_team', true)->exists()) {
            $manager->ownedTeams()->save(Team::forceCreate([
                'user_id' => $manager->id,
                'name' => 'Marketing Team',
                'personal_team' => true,
            ]));
        }

        $managerTeam = $manager->ownedTeams()->where('personal_team', true)->first();
        $manager->current_team_id = $managerTeam->id;
        $manager->save();

        // 3. Agent
        $agent = User::updateOrCreate(
            ['email' => 'agent@example.com'],
            [
                'name' => 'Support Agent',
                'password' => Hash::make('password'),
                'is_super_admin' => false,
            ]
        );

        // Add Agent to Manager's Team
        if (! $managerTeam->users->contains($agent->id)) {
            $managerTeam->users()->attach($agent, ['role' => 'agent']);
        }

        $agent->current_team_id = $managerTeam->id;
        $agent->save();

        // Seed rich mock conversations & messages for both teams
        $this->seedConversationsForTeam($adminTeam);
        $this->seedConversationsForTeam($managerTeam);
    }

    private function seedConversationsForTeam(Team $team)
    {
        $contactsData = [
            ['name' => 'Alice Smith', 'phone' => '+14155550101', 'messages' => [
                ['dir' => 'inbound', 'txt' => 'Hello, I have a question about your plans.'],
                ['dir' => 'outbound', 'txt' => 'Sure! We offer Basic, Pro, and Enterprise plans.'],
                ['dir' => 'inbound', 'txt' => 'Can I upgrade my plan later?'],
            ]],
            ['name' => 'Bob Johnson', 'phone' => '+14155550102', 'messages' => [
                ['dir' => 'inbound', 'txt' => 'Is there a free trial available?'],
                ['dir' => 'outbound', 'txt' => 'Yes, we offer a 14-day free trial on all plans!'],
            ]],
            ['name' => 'Charlie Brown', 'phone' => '+14155550103', 'messages' => [
                ['dir' => 'inbound', 'txt' => 'My bot is paused. How do I turn it back on?'],
            ]],
            ['name' => 'Diana Prince', 'phone' => '+14155550104', 'messages' => [
                ['dir' => 'inbound', 'txt' => 'Hello, do you support webhook integrations?'],
                ['dir' => 'outbound', 'txt' => 'Yes, we support custom webhooks out of the box.'],
                ['dir' => 'inbound', 'txt' => 'Excellent. I will set it up today.'],
            ]],
            ['name' => 'Evan Wright', 'phone' => '+14155550105', 'messages' => [
                ['dir' => 'inbound', 'txt' => 'I would like to request a refund for this month.'],
            ]]
        ];

        foreach ($contactsData as $data) {
            // Verify if contact exists to prevent duplicates
            $contact = \App\Models\Contact::updateOrCreate(
                ['team_id' => $team->id, 'phone_number' => $data['phone']],
                [
                    'name' => $data['name'],
                    'opt_in_status' => 'opted_in',
                ]
            );

            $conversation = \App\Models\Conversation::updateOrCreate(
                ['team_id' => $team->id, 'contact_id' => $contact->id],
                [
                    'status' => 'open',
                    'last_message_at' => now()->subMinutes(rand(1, 120)),
                ]
            );

            foreach ($data['messages'] as $index => $msg) {
                \App\Models\Message::updateOrCreate(
                    [
                        'team_id' => $team->id,
                        'contact_id' => $contact->id,
                        'conversation_id' => $conversation->id,
                        'whatsapp_message_id' => 'mid.mock_' . $team->id . '_' . $contact->id . '_' . $index,
                    ],
                    [
                        'content' => $msg['txt'],
                        'type' => 'text',
                        'status' => 'sent',
                        'direction' => $msg['dir'],
                        'created_at' => now()->subHours(count($data['messages']) - $index),
                    ]
                );
            }
        }
    }
}
