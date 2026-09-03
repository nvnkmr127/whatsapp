<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Conversation;
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
        if (! $team) {
            return response()->json(['error' => 'No team context'], 400);
        }

        $cleanPhone = trim($phone);
        $noPlus = ltrim($cleanPhone, '+');
        $withPlus = '+' . $noPlus;

        $contact = Contact::where('team_id', $team->id)
            ->where(function ($q) use ($cleanPhone, $noPlus, $withPlus) {
                $q->where('phone_number', $cleanPhone)
                    ->orWhere('phone_number', $withPlus)
                    ->orWhere('phone_number', $noPlus);
            })
            ->first();

        if (! $contact) {
            return response()->json(['data' => []]);
        }

        $conversation = Conversation::where('contact_id', $contact->id)->with([
            'messages' => function ($q) {
                $q->latest()->take(50);
            },
        ])->first();

        if (! $conversation) {
            return response()->json(['data' => []]);
        }

        return response()->json([
            'data' => $conversation->messages->reverse()->values(),
        ]);
    }

    /**
     * Send a message to a contact.
     * POST /api/v1/messages
     */
    public function send(Request $request)
    {
        // Support Meta-style 'to' or 'phone' alias and clean formatting (spaces, hyphens)
        $rawPhone = $request->input('phone_number') ?? $request->input('to') ?? $request->input('phone');
        if ($rawPhone) {
            $cleaned = preg_replace('/[\s\-\(\)]+/', '', (string) $rawPhone);
            $request->merge(['phone_number' => $cleaned]);
        }
        
        if ($request->input('type') === 'text' && $request->has('text.body') && !$request->has('message')) {
            $request->merge(['message' => $request->input('text.body')]);
        }

        if ($request->input('type') === 'template' && $request->has('template')) {
            $templateData = $request->input('template');
            
            $mergedData = [
                'template_name' => $templateData['name'] ?? $request->input('template_name'),
                'language' => $templateData['language']['code'] ?? $request->input('language'),
            ];

            if (isset($templateData['components']) && is_array($templateData['components'])) {
                foreach ($templateData['components'] as $component) {
                    if ($component['type'] === 'body' && isset($component['parameters'])) {
                        $mergedData['variables'] = array_map(fn($p) => $p['text'] ?? '', $component['parameters']);
                    }
                    if ($component['type'] === 'header' && isset($component['parameters'])) {
                        $mergedData['header_variables'] = array_map(fn($p) => $p['text'] ?? '', $component['parameters']);
                    }
                }
            }
            
            $request->merge($mergedData);
        }

        $request->validate([
            'phone_number' => ['required', 'string', 'regex:/^\+?[1-9]\d{1,14}$/'],
            'type' => 'required|in:text,template',
            'message' => 'required_if:type,text|string',
            'template_name' => 'required_if:type,template|string',
            'language' => 'required_if:type,template|string|min:2|max:10',
            'variables' => 'array',
            'header_variables' => 'array',
            'footer_variables' => 'array',
        ]);

        $team = $request->user()->currentTeam;
        if (! $team) {
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
                    'status' => 'queued_previously',
                ], 200);
            }
            // Lock Key for 24 hours
            \Illuminate\Support\Facades\Cache::put($cacheKey, true, 60 * 60 * 24);
        }

        // 1. Resolve Contact & Conversation
        $contact = \App\Models\Contact::firstOrCreate(
            ['team_id' => $team->id, 'phone_number' => $request->phone_number]
        );
        $conversation = (new \App\Services\ConversationService)->ensureActiveConversation($contact);

        // 2. Pre-persist
        $message = \App\Models\Message::create([
            'team_id' => $team->id,
            'contact_id' => $contact->id,
            'conversation_id' => $conversation->id,
            'type' => $request->type,
            'direction' => 'outbound',
            'status' => 'queued',
            'content' => $request->type === 'text' ? $request->message : "Template: {$request->template_name}",
            'metadata' => $request->type === 'template' ? [
                'template_name' => $request->template_name,
                'language' => $request->language ?? 'en_US',
                'variables' => $request->variables ?? [],
                'header_variables' => $request->header_variables ?? [],
                'footer_variables' => $request->footer_variables ?? [],
            ] : [],
        ]);

        // Dispatch Job
        \App\Jobs\SendMessageJob::dispatch(
            $team->id,
            $request->phone_number,
            $request->type,
            $request->type === 'text' ? $request->message : ($request->variables ?? []),
            $request->template_name ?? null,
            $request->language ?? 'en_US',
            $message->id,
            null,
            $request->header_variables ?? [],
            $request->footer_variables ?? []
        );

        return response()->json([
            'success' => true,
            'message' => 'Message queued for sending.',
            'status' => 'queued',
        ], 202);
    }
}
