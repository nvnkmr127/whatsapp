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

        $contacts = $query->paginate(min((int) $request->input('per_page', 40), 100));

        return response()->json($contacts);
    }

    /**
     * Create a new contact for the current team.
     */
    public function store(Request $request)
    {
        $team = $request->user()->currentTeam;
        if (!$team) {
            return response()->json(['error' => 'No active team selected'], 403);
        }

        $validated = $request->validate([
            'phone_number' => ['required', 'string', 'regex:/^\+?[1-9]\d{1,14}$/'], // E.164-ish compliance
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'custom_attributes' => 'nullable|array',
            'custom_attributes.email' => 'nullable|email|max:255',
            'custom_attributes.company' => 'nullable|string|max:255',
            'opt_in_status' => 'nullable|string|in:opted_in,opted_out,pending',
            'conversation_id' => 'nullable|integer',
        ]);

        // Check if a contact with this phone number already exists in this team
        $existing = Contact::where('team_id', $team->id)
            ->where('phone_number', $validated['phone_number'])
            ->first();

        if ($existing) {
            if (!empty($validated['conversation_id'])) {
                $conversation = \App\Models\Conversation::where('team_id', $team->id)->find($validated['conversation_id']);
                if ($conversation && !$conversation->contact_id) {
                    $conversation->update(['contact_id' => $existing->id]);
                }
            }
            $conversation = app(\App\Services\ConversationService::class)->ensureActiveConversation($existing);
            $lastMsg = $conversation->lastMessage;

            return response()->json([
                'message' => 'Contact already exists.',
                'contact' => $existing,
                'conversation' => [
                    'id' => $conversation->id,
                    'contact_id' => $conversation->contact_id,
                    'name' => $existing->name,
                    'phone' => $existing->phone_number,
                    'last' => $lastMsg ? $lastMsg->content : 'No messages yet',
                    'time' => $lastMsg ? $lastMsg->created_at->format('H:i') : '',
                    'unread' => $conversation->messages()->where('direction', 'inbound')->whereNull('read_at')->count(),
                    'status' => $conversation->status === 'closed' ? 'delivered' : 'read',
                    'online' => $conversation->isWithin24Hours(),
                    'pinned' => false,
                    'bot' => !($existing->is_bot_paused ?? false),
                ],
            ], 409);
        }

        $customAttributes = $request->input('custom_attributes', []);
        $email = $validated['email'] ?? ($customAttributes['email'] ?? null);
        $companyId = null;

        if (isset($customAttributes['company']) && $customAttributes['company'] !== '') {
            $company = \App\Models\Company::firstOrCreate(
                ['team_id' => $team->id, 'name' => $customAttributes['company']]
            );
            $companyId = $company->id;
        }

        unset($customAttributes['email'], $customAttributes['company']);

        $contact = Contact::create([
            'team_id' => $team->id,
            'phone_number' => $validated['phone_number'],
            'name' => $validated['name'] ?? $validated['phone_number'],
            'email' => $email,
            'company_id' => $companyId,
            'custom_attributes' => $customAttributes,
            'opt_in_status' => $validated['opt_in_status'] ?? 'opted_in',
        ]);

        if (!empty($validated['conversation_id'])) {
            $conversation = \App\Models\Conversation::where('team_id', $team->id)->find($validated['conversation_id']);
            if ($conversation) {
                $conversation->update(['contact_id' => $contact->id]);
            } else {
                $conversation = app(\App\Services\ConversationService::class)->ensureActiveConversation($contact);
            }
        } else {
            $conversation = app(\App\Services\ConversationService::class)->ensureActiveConversation($contact);
        }

        return response()->json([
            'success' => true,
            'contact' => $contact->load('companyRelation'),
            'conversation' => [
                'id' => $conversation->id,
                'contact_id' => $conversation->contact_id,
                'name' => $contact->name,
                'phone' => $contact->phone_number,
                'last' => 'No messages yet',
                'time' => '',
                'unread' => 0,
                'status' => 'new',
                'online' => false,
                'pinned' => false,
                'bot' => true,
            ],
        ], 201);
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
            'email' => 'nullable|email|max:255',
            'custom_attributes' => 'nullable|array',
            'custom_attributes.email' => 'nullable|email|max:255',
            'custom_attributes.company' => 'nullable|string|max:255',
            'opt_in_status' => 'nullable|string|in:opted_in,opted_out,pending',
        ]);

        $updateData = $request->only('name', 'opt_in_status');
        $customAttrs = $request->input('custom_attributes', $contact->custom_attributes ?? []);
        
        if ($request->has('custom_attributes.email')) {
            $updateData['email'] = $request->input('custom_attributes.email');
        } elseif ($request->has('email')) {
            $updateData['email'] = $request->input('email');
        }

        if (array_key_exists('company', $customAttrs)) {
            if (empty($customAttrs['company'])) {
                $updateData['company_id'] = null;
            } else {
                $company = \App\Models\Company::firstOrCreate(
                    ['team_id' => $contact->team_id, 'name' => $customAttrs['company']]
                );
                $updateData['company_id'] = $company->id;
            }
        }

        unset($customAttrs['email'], $customAttrs['company']);
        $updateData['custom_attributes'] = $customAttrs;

        $contact->update($updateData);

        return response()->json([
            'success' => true,
            'contact' => $contact->load('tags', 'companyRelation'),
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
     * Create a new tag for the current team.
     */
    public function storeTag(Request $request)
    {
        $team = $request->user()->currentTeam;
        if (! $team) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:50',
            'color' => 'nullable|string|regex:/^#[a-fA-F0-9]{6}$/',
        ]);

        $existing = \App\Models\ContactTag::where('team_id', $team->id)
            ->where('name', $request->name)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'A tag with this name already exists.',
                'tag' => $existing,
            ], 422);
        }

        $tag = \App\Models\ContactTag::create([
            'team_id' => $team->id,
            'name' => $request->name,
            'color' => $request->color ?? '#10B981',
        ]);

        \Illuminate\Support\Facades\Cache::forget("team_{$team->id}_tags");

        return response()->json([
            'success' => true,
            'tag' => $tag,
        ], 201);
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
