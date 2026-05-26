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
        try {
            $user = $request->user();
            $team = $user->currentTeam;

            if (!$team) {
                return response()->json(['error' => 'No team associated'], 400);
            }

            $query = Conversation::where('team_id', $team->id);

            // --- GLOBAL SCOPING ---
            if ($numberId = $request->header('X-WhatsApp-Number-ID')) {
                // The whatsapp_phone_number_id is on the team, not the conversation.
                // We verify it matches the current team's number to ensure context.
                if ($team->whatsapp_phone_number_id != $numberId) {
                    return response()->json([
                        'data' => [],
                        'current_page' => 1,
                        'last_page' => 1,
                        'total' => 0
                    ]);
                }
            }

            if ($memberId = $request->header('X-Member-ID')) {
                $query->where('assigned_to', $memberId);
            }

            // Clone base query for count calculations before filters and search are applied
            $baseQuery = clone $query;

            // Apply Filters
            $filter = $request->input('filter', 'all');
            if ($filter === 'unread') {
                $query->whereHas('messages', function ($q) {
                    $q->where('direction', 'inbound')->whereNull('read_at');
                });
            } elseif ($filter === 'assigned') {
                $query->where('assigned_to', $user->id);
            } elseif ($filter === 'open') {
                $query->where('status', '!=', 'closed');
            } elseif ($filter === 'bots') {
                $query->whereHas('contact', function ($q) {
                    $q->where(function ($sub) {
                        $sub->where('is_bot_paused', false)->orWhereNull('is_bot_paused');
                    });
                });
            }

            // Search Support
            $search = $request->input('query') ?? $request->input('search');
            if ($search) {
                $query->whereHas('contact', function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%")->orWhere('phone_number', 'like', "%$search%");
                });
            }

            $conversations = $query->with([
                'contact:id,name,phone_number,is_bot_paused',
                'lastMessage',
                'assignee:id,name',
                'contact.tags',
                'lastInboundMessage',
            ])
                ->withCount([
                    'messages as unread_count' => function ($query) {
                        $query->where('direction', 'inbound')->whereNull('read_at');
                    }
                ])
                ->orderBy('last_message_at', 'desc')
                ->paginate(min((int) $request->input('per_page', 20), 100));

            // Transform to match WhatsApp-like expectations
            $conversations->getCollection()->transform(function ($conv) {
                return [
                    'id' => $conv->id,
                    'contact_id' => $conv->contact_id,
                    'name' => $conv->contact?->name ?? $conv->contact?->phone_number ?? 'Unknown Contact',
                    'phone' => $conv->contact?->phone_number ?? 'Unknown',
                    'last_message' => $conv->lastMessage ? [
                        'content' => $conv->lastMessage->content,
                        'type' => $conv->lastMessage->type,
                        'is_outbound' => $conv->lastMessage->direction === 'outbound',
                        'timestamp' => $conv->lastMessage->created_at->timestamp,
                        'pretty_time' => $conv->lastMessage->created_at->format('H:i'),
                    ] : null,
                    'unread_count' => $conv->unread_count,
                    'status' => $conv->status,
                    'assignee' => $conv->assignee ? [
                        'id' => $conv->assignee->id,
                        'name' => $conv->assignee->name,
                        'initials' => strtoupper(substr($conv->assignee->name, 0, 2)),
                    ] : null,
                    'tags' => $conv->contact?->tags ? $conv->contact->tags->map(fn($t) => [
                        'name' => $t->name,
                        'color' => $t->color ?? '#E91E63',
                    ]) : [],
                    'last_interaction' => $conv->last_message_at ? $conv->last_message_at->timestamp : null,
                    'is_within_24_hours' => $conv->isWithin24Hours(),
                    'is_ai_enabled' => !($conv->contact?->is_bot_paused ?? false),
                ];
            });

            // Calculate True Counts on the Server
            $allCount = (clone $baseQuery)->count();
            
            $unreadCount = (clone $baseQuery)
                ->whereHas('messages', function ($q) {
                    $q->where('direction', 'inbound')->whereNull('read_at');
                })->count();
                
            $openCount = (clone $baseQuery)
                ->where('status', '!=', 'closed')
                ->count();
                
            $mineCount = (clone $baseQuery)
                ->where('assigned_to', $user->id)
                ->count();
                
            $botsCount = (clone $baseQuery)
                ->whereHas('contact', function ($q) {
                    $q->where(function ($sub) {
                        $sub->where('is_bot_paused', false)->orWhereNull('is_bot_paused');
                    });
                })->count();

            $responseData = $conversations->toArray();
            $responseData['counts'] = [
                'All' => $allCount,
                'Unread' => $unreadCount,
                'Open' => $openCount,
                'Mine' => $mineCount,
                'Bots' => $botsCount,
            ];

            return response()->json($responseData);
        } catch (\Exception $e) {
            \Log::error('[MOBILE_INBOX_ERROR] ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Internal Server Error',
                'error' => app()->environment('local') ? $e->getMessage() : 'An unexpected error occurred.'
            ], 500);
        }
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

        // Calculate session window expiry from last inbound message
        $lastInbound = $conversation->messages()
            ->where('direction', 'inbound')
            ->latest('created_at')
            ->first();

        $sessionExpiresAt = null;
        if ($lastInbound) {
            $expiry = $lastInbound->created_at->addHours(24);
            if ($expiry->isFuture()) {
                $sessionExpiresAt = $expiry->toIso8601String();
            }
        }

        return response()->json([
            'id' => $conversation->id,
            'contact' => $conversation->contact,
            'unread_count' => $conversation->unread_count,
            'status' => $conversation->status,
            'assigned_to' => $conversation->assigned_to,
            'metadata' => $conversation->metadata,
            'is_within_24_hours' => $conversation->isWithin24Hours(),
            'is_ai_enabled' => !($conversation->contact->is_bot_paused ?? false),
            // ISO8601 expiry timestamp for mobile live countdown timer.
            // null means the 24h window is closed (no inbound message in last 24h).
            'session_expires_at' => $sessionExpiresAt,
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
        if (!$team) {
            return response()->json([]);
        }

        return response()->json(\App\Models\CannedMessage::where('team_id', $team->id)->orderBy('shortcut')->limit(200)->get());
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

        // Trigger CSAT Survey
        try {
            app(\App\Services\CsatService::class)->sendSurvey($conversation);
        } catch (\Exception $e) {
            \Log::warning("Failed to send CSAT survey for Conversation #{$conversation->id}: " . $e->getMessage());
        }

        return response()->json(['success' => true]);
    }

    /**
     * Reopen a closed/resolved conversation.
     */
    public function reopen(Request $request, Conversation $conversation)
    {
        $this->authorizeConversation($request->user(), $conversation);

        $conversation->update([
            'status' => 'open',
            'closed_at' => null,
        ]);

        return response()->json(['success' => true, 'status' => 'open']);
    }

    /**
     * Toggle AI Assistant (Bot) for the conversation's contact.
     */
    public function toggleAi(Request $request, Conversation $conversation)
    {
        $this->authorizeConversation($request->user(), $conversation);
        $contact = $conversation->contact;

        $isEnabled = (bool) $request->input('enabled', false);

        $contact->update([
            'is_bot_paused' => !$isEnabled,
            'bot_paused_at' => !$isEnabled ? now() : null,
            'bot_paused_reason' => !$isEnabled ? 'Manually paused by agent via mobile' : null,
        ]);

        return response()->json([
            'success' => true,
            'is_ai_enabled' => $isEnabled,
        ]);
    }

    protected function authorizeConversation($user, $conversation)
    {
        if ($conversation->team_id !== $user->currentTeam?->id && !$user->isSuperAdmin()) {
            abort(403, 'Unauthorized access to this conversation.');
        }
    }
}
