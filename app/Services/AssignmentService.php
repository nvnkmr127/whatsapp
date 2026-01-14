<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Team;
use App\Models\User;

class AssignmentService
{
    /**
     * Automatically assign a contact to an agent based on load.
     */
    public function assignToBestAgent(Team $team, Contact $contact)
    {
        // 1. If already assigned, do nothing
        if ($contact->assigned_to) {
            return;
        }

        // 2. Get eligible agents who have "receives_tickets" enabled
        $agents = $team->users()->wherePivot('receives_tickets', true)->get();

        // Also add the owner if they should receive tickets (Jetstream teams usually have users + owner)
        // However, in many implementations, the owner is also in the users() list if it's a personal team or they joined.
        // Let's stick to the users() for now based on the UI.

        if ($agents->isEmpty()) {
            return;
        }

        // 3. Load Balancing Strategy: Find agent with fewest open assignments
        $bestAgent = null;
        $minLoad = PHP_INT_MAX;

        foreach ($agents as $agent) {
            $load = Contact::where('team_id', $team->id)
                ->where('assigned_to', $agent->id)
                ->whereNotIn('status', ['resolved', 'closed']) // Only count open/pending
                ->count();

            if ($load < $minLoad) {
                $minLoad = $load;
                $bestAgent = $agent;
            }
        }


        // 4. Assign
        if ($bestAgent) {
            $contact->update(['assigned_to' => $bestAgent->id]);

            // 5. Create System Note
            $contact->notes()->create([
                'team_id' => $team->id,
                'user_id' => $bestAgent->id,
                'body' => "Configuration: System automatically assigned to {$bestAgent->name} (Load Balancing).",
                'type' => 'system'
            ]);
        }
    }
    public function createSystemNote(Team $team, Contact $contact, string $body)
    {
        $contact->notes()->create([
            'team_id' => $team->id,
            'user_id' => $team->user_id, // Owner as system? Or create without user_id if nullable
            'body' => $body,
            'type' => 'system'
        ]);
    }
}
