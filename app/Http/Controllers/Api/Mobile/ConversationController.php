<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    /**
     * Get a paginated list of conversations for the mobile inbox.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $team = $user->currentTeam;

        if (! $team) {
            return response()->json(['error' => 'No team associated'], 400);
        }

        $query = Conversation::where('team_id', $team->id);

        // Apply Filters
        $filter = $request->input('filter', 'all');
        if ($filter === 'unread') {
            $query->whereHas('messages', function($q) {
                $q->where('direction', 'inbound')->whereNull('read_at');
            });
        } elseif ($filter === 'assigned') {
            $query->where('assigned_to', $user->id);
        }

        $conversations = $query->with(['contact:id,name,phone_number', 'lastMessage'])
            ->withCount(['messages as unread_count' => function ($query) {
                $query->where('direction', 'inbound')->whereNull('read_at');
            }])
            ->orderBy('last_message_at', 'desc')
            ->paginate($request->input('per_page', 20));

        // Transform to match WhatsApp-like expectations
        $conversations->getCollection()->transform(function ($conv) {
            return [
                'id' => $conv->id,
                'name' => $conv->contact->name ?? $conv->contact->phone_number,
                'phone' => $conv->contact->phone_number,
                'last_message' => $conv->lastMessage ? [
                    'content' => $conv->lastMessage->content,
                    'type' => $conv->lastMessage->type,
                    'is_outbound' => $conv->lastMessage->direction === 'outbound',
                    'timestamp' => $conv->lastMessage->created_at->timestamp,
                    'pretty_time' => $conv->lastMessage->created_at->format('H:i'),
                ] : null,
                'unread_count' => $conv->unread_count,
                'status' => $conv->status,
                'assigned_to' => $conv->assigned_to,
                'last_interaction' => $conv->last_message_at ? $conv->last_message_at->timestamp : null,
                'is_within_24_hours' => $conv->isWithin24Hours(),
            ];
        });

        return response()->json($conversations);
    }

    /**
     * Get single conversation details.
     */
    public function show(Request $request, Conversation $conversation)
    {
        $this->authorizeConversation($request->user(), $conversation);

        $conversation->load(['contact', 'lastMessage']);
        $conversation->unread_count = $conversation->messages()
            ->where('direction', 'inbound')
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'id' => $conversation->id,
            'contact' => $conversation->contact,
            'unread_count' => $conversation->unread_count,
            'status' => $conversation->status,
            'assigned_to' => $conversation->assigned_to,
            'metadata' => $conversation->metadata,
            'is_within_24_hours' => $conversation->isWithin24Hours(),
        ]);
    }

    /**
     * Mark a conversation as read.
     */
    public function markAsRead(Request $request, Conversation $conversation)
    {
        $this->authorizeConversation($request->user(), $conversation);

        $conversation->messages()
            ->where('direction', 'inbound')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * Get internal notes for a conversation.
     */
    public function getNotes(Request $request, Conversation $conversation)
    {
        $this->authorizeConversation($request->user(), $conversation);

        return response()->json($conversation->notes()->with('user:id,name')->get());
    }

    /**
     * Store a new internal note.
     */
    public function storeNote(Request $request, Conversation $conversation)
    {
        $this->authorizeConversation($request->user(), $conversation);

        $request->validate([
            'content' => 'required|string',
        ]);

        $note = $conversation->notes()->create([
            'team_id' => $conversation->team_id,
            'user_id' => $request->user()->id,
            'content' => $request->input('content'),
        ]);

        return response()->json($note->load('user:id,name'));
    }

    /**
     * Get canned messages for quick replies.
     */
    public function getCannedMessages(Request $request)
    {
        $team = $request->user()->currentTeam;
        if (! $team) {
            return response()->json([]);
        }

        return response()->json(\App\Models\CannedMessage::where('team_id', $team->id)->orderBy('shortcut')->get());
    }

    /**
     * Assign a conversation to an agent (or self).
     */
    public function assign(Request $request, Conversation $conversation)
    {
        $this->authorizeConversation($request->user(), $conversation);

        $request->validate(['user_id' => 'nullable|exists:users,id']);

        $assigneeId = $request->input('user_id') ?? $request->user()->id;

        // Ensure assignee is in the team
        $assignee = \App\Models\User::findOrFail($assigneeId);
        if ($assignee->currentTeam?->id !== $conversation->team_id) {
            return response()->json(['error' => 'Assignee must be a member of the same team.'], 422);
        }

        $conversation->update(['assigned_to' => $assigneeId]);

        return response()->json([
            'success' => true,
            'assignee' => $assignee->only('id', 'name'),
        ]);
    }

    /**
     * Close/Archive a conversation.
     */
    public function close(Request $request, Conversation $conversation)
    {
        $this->authorizeConversation($request->user(), $conversation);

        $conversation->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    protected function authorizeConversation($user, $conversation)
    {
        if ($conversation->team_id !== $user->currentTeam?->id && ! $user->isSuperAdmin()) {
            abort(403, 'Unauthorized access to this conversation.');
        }
    }
}
