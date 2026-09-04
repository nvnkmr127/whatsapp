<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Conversation;
use App\Traits\StandardApiResponses;
use Illuminate\Http\Request;

class ExternalConversationController extends Controller
{
    use StandardApiResponses;

    /**
     * Get messages for a contact.
     * GET /api/v1/conversations/{phone}
     */
    public function index(Request $request, $phone)
    {
        $team = $request->user()->currentTeam;
        if (! $team) {
            return $this->error('No team context selected.', 400, null, 'ERR_NO_TEAM_CONTEXT');
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
            return $this->success([], 'No conversation found.');
        }

        $conversation = Conversation::where('contact_id', $contact->id)
            ->latest('last_message_at')
            ->latest('id')
            ->with([
                'messages' => function ($q) {
                    $q->latest()->take(50);
                },
            ])->first();

        if (! $conversation) {
            return $this->success([], 'No conversation found.');
        }

        return $this->success(
            $conversation->messages->reverse()->values(),
            'Conversation retrieved successfully.'
        );
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
            return $this->error('No team context selected.', 400, null, 'ERR_NO_TEAM_CONTEXT');
        }

        // Idempotency Check — Cache::add is atomic (set-if-absent), so concurrent
        // requests with the same key can't both slip through a check-then-put race.
        $idempotencyKey = $request->header('X-Idempotency-Key');
        if ($idempotencyKey) {
            $cacheKey = "idempotency_send_{$team->id}_{$idempotencyKey}";
            if (! \Illuminate\Support\Facades\Cache::add($cacheKey, true, 60 * 60 * 24)) {
                return $this->success(
                    ['status' => 'queued_previously'],
                    'Request already processed (Idempotent)',
                    200
                );
            }
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
        try {
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
        } catch (\Throwable $e) {
            $message->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            return $this->error('Failed to dispatch message: ' . $e->getMessage(), 500);
        }

        return $this->success(
            [
                'status' => 'queued',
                'message_id' => $message->id,
                'conversation_id' => $conversation->id,
            ],
            'Message queued for sending.',
            202
        );
    }
}
