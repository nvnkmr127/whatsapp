<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * Get paginated contacts for the current team.
     */
    public function index(Request $request)
    {
        $team = $request->user()->currentTeam;
        if (!$team) return response()->json([]);

        $query = Contact::where('team_id', $team->id)
            ->with(['tags'])
            ->withCount('messages');

        // Search
        if ($request->filled('query')) {
            $q = $request->input('query');
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('phone_number', 'like', "%{$q}%");
            });
        }

        // Sort
        $sort = $request->input('sort', 'last_activity');
        if ($sort === 'last_activity') {
            $query->latest('updated_at');
        } elseif ($sort === 'name') {
            $query->orderBy('name');
        }

        $contacts = $query->paginate($request->input('per_page', 40));

        return response()->json($contacts);
    }

    /**

     * Get details for a specific contact.
     */
    public function show(Request $request, Contact $contact)
    {
        $this->authorizeContact($request->user(), $contact);

        $team = $request->user()->currentTeam;
        $schema = \App\Models\ContactField::where('team_id', $team->id)->get();

        return response()->json([
            'contact' => $contact->load([
                'tags', 
                'activeConversation', 
                'notes.user', 
                'contactEvents' => fn($q) => $q->latest()->take(10)
            ]),
            'schema' => $schema
        ]);
    }

    /**
     * Get the full activity timeline for a contact.
     */
    public function activity(Request $request, Contact $contact)
    {
        $this->authorizeContact($request->user(), $contact);

        $events = \App\Models\ContactEvent::where('contact_id', $contact->id)
            ->latest()
            ->paginate(30);

        return response()->json($events);
    }

    /**

     * Update details for a contact (e.g. name, custom attributes).
     */
    public function update(Request $request, Contact $contact)
    {
        $this->authorizeContact($request->user(), $contact);

        $request->validate([
            'name' => 'nullable|string|max:255',
            'custom_attributes' => 'nullable|array',
            'opt_in_status' => 'nullable|string|in:opted_in,opted_out,pending',
        ]);

        $contact->update($request->only('name', 'custom_attributes', 'opt_in_status'));

        return response()->json([
            'success' => true,
            'contact' => $contact->load('tags'),
        ]);
    }

    /**
     * Get all available tags for the team.
     */
    public function getAvailableTags(Request $request)
    {
        $team = $request->user()->currentTeam;
        if (! $team) {
            return response()->json([]);
        }

        return response()->json(\App\Models\ContactTag::where('team_id', $team->id)->get());
    }

    /**
     * Toggle a tag for a contact.
     */
    public function toggleTag(Request $request, Contact $contact)
    {
        $this->authorizeContact($request->user(), $contact);

        $request->validate(['tag_id' => 'required|exists:contact_tags,id']);

        $tag = \App\Models\ContactTag::findOrFail($request->tag_id);

        // Security check for tag team
        if ($tag->team_id !== $contact->team_id) {
            abort(403);
        }

        $contact->tags()->toggle($tag->id);

        return response()->json([
            'success' => true,
            'contact' => $contact->load('tags'),
        ]);
    }

    /**
     * Search contacts for quick lookup.
     */
    public function search(Request $request)
    {
        $query = $request->input('query');
        $team = $request->user()->currentTeam;

        if (! $team) {
            return response()->json([]);
        }

        $contacts = Contact::where('team_id', $team->id)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('phone_number', 'like', "%{$query}%");
            })
            ->take(20)
            ->get();

        return response()->json($contacts);
    }

    protected function authorizeContact($user, $contact)
    {
        if ($contact->team_id !== $user->currentTeam?->id && ! $user->isSuperAdmin()) {
            abort(403, 'Unauthorized access to this contact.');
        }
    }
}
