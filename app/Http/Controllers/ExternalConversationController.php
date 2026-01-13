<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Conversation;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;

class ExternalConversationController extends Controller
{
    /**
     * Get messages for a contact.
     * GET /api/v1/conversations/{phone}
     */
    public function index(Request $request, $phone)
    {
        $team = $request->user()->currentTeam;

        $contact = Contact::where('team_id', $team->id)->where('phone_number', $phone)->first();

        if (!$contact) {
            return response()->json(['data' => []]);
        }

        $conversation = Conversation::where('contact_id', $contact->id)->with([
            'messages' => function ($q) {
                $q->latest()->take(50);
            }
        ])->first();

        if (!$conversation) {
            return response()->json(['data' => []]);
        }

        return response()->json([
            'data' => $conversation->messages->reverse()->values()
        ]);
    }

    /**
     * Send a message to a contact.
     * POST /api/v1/messages
     */
    public function send(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'type' => 'required|in:text,template',
            'message' => 'required_if:type,text|string',
            'template_name' => 'required_if:type,template|string',
            'language' => 'required_if:type,template|string|size:5',
            'variables' => 'array',
        ]);

        $team = $request->user()->currentTeam;
        if (!$team) {
            return response()->json(['error' => 'No Team Context'], 400);
        }

        // Idempotency Check
        $idempotencyKey = $request->header('X-Idempotency-Key');
        if ($idempotencyKey) {
            $cacheKey = "idempotency_send_{$team->id}_{$idempotencyKey}";
            if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Request already processed (Idempotent)',
                    'status' => 'queued_previously'
                ], 200);
            }
            // Lock Key for 24 hours
            \Illuminate\Support\Facades\Cache::put($cacheKey, true, 60 * 60 * 24);
        }

        // Dispatch Job
        \App\Jobs\SendMessageJob::dispatch(
            $team->id,
            $request->phone_number,
            $request->type,
            $request->type === 'text' ? $request->message : ($request->variables ?? []),
            $request->template_name ?? null,
            $request->language ?? 'en_US'
        );

        return response()->json([
            'success' => true,
            'message' => 'Message queued for sending.',
            'status' => 'queued'
        ], 202);
    }
}
