<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /**
     * Get cursor-paginated messages for a conversation.
     * cursor is the message ID to start from.
     */
    public function index(Request $request, Conversation $conversation)
    {
        $this->authorizeConversation($request->user(), $conversation);

        $messages = Message::where('contact_id', $conversation->contact_id)
            ->with('replyTo:id,content')
            ->latest('id')
            ->cursorPaginate($request->input('per_page', 40));

        // Transform to include reply context
        $messages->getCollection()->transform(function($msg) {
            $data = $msg->toArray();
            if ($msg->replyTo) {
                $data['reply_to_content'] = $msg->replyTo->content;
            }
            // Ensure full URL for media
            if ($msg->media_url) {
                $data['media_url'] = $msg->full_media_url;
            }
            return $data;
        });

        // Mark messages as read if retrieved via mobile app
        $conversation->messages()
            ->where('direction', 'inbound')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json($messages);
    }

    /**
     * Send a message from mobile.
     */
    public function store(Request $request, Conversation $conversation)
    {
        $this->authorizeConversation($request->user(), $conversation);

        $request->validate([
            'type' => 'required|in:text,image,document,template',
            'content' => 'required_if:type,text|string',
            'media_url' => 'required_if:type,image,document|string',
        ]);

        $user = $request->user();
        $team = $user->currentTeam;
        
        // Meta Policy: Check 24-hour window for text/media messages
        if (in_array($request->type, ['text', 'image', 'document']) && !$conversation->isWithin24Hours()) {
            return response()->json([
                'success' => false,
                'error' => 'Policy Violation: 24-hour window closed. Please use a Template message.',
                'code' => 'META_POLICY_WINDOW_CLOSED'
            ], 422);
        }

        // Ensure we use the latest conversation context
        $contact = $conversation->contact;

        $isMedia = in_array($request->type, ['image', 'document', 'video', 'audio']);

        $metadata = [];
        if ($request->input('reply_to_message_id')) {
            $metadata['reply_to_message_id'] = $request->input('reply_to_message_id');
        }
        $metadata['user_id'] = $user->id;

        $message = Message::create([
            'team_id' => $team->id,
            'contact_id' => $conversation->contact_id,
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'type' => $request->input('type', 'text'),
            'content' => $request->input('content'),
            'caption' => $isMedia ? $request->input('content') : null,
            'media_url' => $request->input('media_url'),
            'metadata' => empty($metadata) ? null : $metadata,
            'status' => 'pending',
        ]);

        $jobContent = $isMedia ? $request->input('media_url') : ($request->type === 'text' ? $request->input('content') : []);

        // Dispatch Job asynchronously (do not block the HTTP response)
        \App\Jobs\SendMessageJob::dispatch(
            $team->id,
            $contact->phone_number,
            $request->type,
            $jobContent,
            null, // Template name if template
            'en_US', // Language
            $message->id
        );

        return response()->json([
            'success' => true,
            'message' => $message->load('contact'),
        ]);
    }

    protected function authorizeConversation($user, $conversation)
    {
        if ($conversation->team_id !== $user->currentTeam?->id && ! $user->isSuperAdmin()) {
            abort(403, 'Unauthorized access to this conversation.');
        }
    }

    /**
     * Delete a message (Unsend for everyone).
     */
    public function destroy(Request $request, Message $message)
    {
        $conversation = $message->conversation;
        if ($conversation->team_id !== $request->user()->currentTeam?->id && ! $request->user()->isSuperAdmin()) {
            abort(403);
        }

        // Only outbound messages can be unsent
        if (! $message->is_outbound) {
            return response()->json(['error' => 'Only outbound messages can be deleted.'], 422);
        }

        // Call Meta Cloud API to delete (This would typically happen in a Service)
        // Meta requires: whatsapp_message_id and status='deleted'
        $message->update(['status' => 'deleted', 'content' => 'This message was deleted']);

        // Broadcast a status update event
        event(new \App\Events\MessageStatusUpdated($message));

        return response()->json(['success' => true]);
    }

    /**
     * Forward a message to another conversation.
     */
    public function forward(Request $request, Message $message)
    {
        $request->validate(['to_conversation_id' => 'required|exists:conversations,id']);

        $target = \App\Models\Conversation::findOrFail($request->to_conversation_id);
        $this->authorizeConversation($request->user(), $target);

        $newMsg = $target->messages()->create([
            'team_id' => $target->team_id,
            'user_id' => $request->user()->id,
            'direction' => 'outbound',
            'type' => $message->type,
            'content' => $message->content,
            'media_url' => $message->media_url,
            'status' => 'pending',
        ]);

        \App\Jobs\SendMessageJob::dispatch($newMsg);

        return response()->json(['success' => true]);
    }

    /**
     * Toggle starred status for a message.
     */
    public function toggleStar(Request $request, Message $message)
    {
        $conversation = $message->conversation;
        if ($conversation->team_id !== $request->user()->currentTeam?->id && ! $request->user()->isSuperAdmin()) {
            abort(403);
        }

        $message->update(['is_starred' => ! $message->is_starred]);

        return response()->json([
            'success' => true,
            'is_starred' => $message->is_starred,
        ]);
    }

    /**
     * React to a message with an emoji.
     */
    public function react(Request $request, Message $message)
    {
        $request->validate(['emoji' => 'required|string']);

        $conversation = $message->conversation;
        $this->authorizeConversation($request->user(), $conversation);

        $metadata = $message->metadata ?? [];
        $metadata['reaction'] = $request->emoji;
        $message->update(['metadata' => $metadata]);

        event(new \App\Events\MessageStatusUpdated($message));

        return response()->json(['success' => true]);
    }

    /**
     * Get approved WhatsApp templates for the current team.
     */
    public function getTemplates(Request $request)
    {
        $team = $request->user()->currentTeam;
        if (! $team) {
            return response()->json([]);
        }

        return response()->json(
            \App\Models\WhatsappTemplate::where('team_id', $team->id)
                ->where('status', 'APPROVED')
                ->get()
        );
    }

    /**
     * Send a template message in a conversation.
     */
    public function sendTemplate(Request $request, Conversation $conversation)
    {
        $this->authorizeConversation($request->user(), $conversation);

        $request->validate(['template_id' => 'required', 'variables' => 'nullable|array']);

        $template = \App\Models\WhatsappTemplate::where('team_id', $conversation->team_id)
            ->where('id', $request->template_id)
            ->firstOrFail();

        $message = $conversation->messages()->create([
            'team_id' => $conversation->team_id,
            'user_id' => $request->user()->id,
            'direction' => 'outbound',
            'type' => 'template',
            'content' => 'Official Template: ' . $template->name,
            'status' => 'pending',
            'metadata' => [
                'template_id' => $template->id,
                'template_name' => $template->name,
                'variables' => $request->variables ?? [],
                'language' => $template->language ?? 'en_US'
            ],
        ]);

        \App\Jobs\SendMessageJob::dispatch($message);

        return response()->json(['success' => true]);
    }
}
