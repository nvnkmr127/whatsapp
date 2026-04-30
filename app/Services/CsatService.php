<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\CsatRating;
use App\Models\Team;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

class CsatService
{
    public function __construct(private WhatsAppService $whatsapp) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Send a CSAT survey via WhatsApp interactive buttons after conversation close
    // ─────────────────────────────────────────────────────────────────────────
    public function sendSurvey(Conversation $conversation): bool
    {
        $team    = $conversation->team;
        $contact = $conversation->contact;

        if (! $contact?->phone_number) return false;
        if (! $team?->whatsapp_phone_number_id) return false;

        // Don't send if contact already rated this conversation
        if (CsatRating::where('conversation_id', $conversation->id)->exists()) return false;

        // Build a 5-star interactive button message
        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $contact->phone_number,
            'type'              => 'interactive',
            'interactive'       => [
                'type' => 'button',
                'body' => [
                    'text' => "Hi {$contact->name}! 👋 We've just resolved your conversation.\n\nHow would you rate your experience with us today?",
                ],
                'action' => [
                    'buttons' => [
                        ['type' => 'reply', 'reply' => ['id' => "csat_{$conversation->id}_5", 'title' => '⭐⭐⭐⭐⭐ Excellent']],
                        ['type' => 'reply', 'reply' => ['id' => "csat_{$conversation->id}_4", 'title' => '⭐⭐⭐⭐ Good']],
                        ['type' => 'reply', 'reply' => ['id' => "csat_{$conversation->id}_3", 'title' => '⭐⭐⭐ Okay']],
                    ],
                ],
            ],
        ];

        try {
            $this->whatsapp->sendRaw($team, $payload);
            Log::info("[CSAT] Survey sent for conversation #{$conversation->id}");
            return true;
        } catch (\Exception $e) {
            Log::error("[CSAT] Survey send failed: " . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Record a rating from an incoming WhatsApp button reply
    // Call this from your webhook handler when button_reply.id starts with "csat_"
    // ─────────────────────────────────────────────────────────────────────────
    public function recordFromWebhook(string $buttonId, int $contactId): ?CsatRating
    {
        // buttonId format: csat_{conversationId}_{rating}
        if (! str_starts_with($buttonId, 'csat_')) return null;

        [$prefix, $conversationId, $rating] = explode('_', $buttonId);
        $conversation = Conversation::find((int) $conversationId);
        if (! $conversation) return null;

        // Prevent duplicate ratings
        if (CsatRating::where('conversation_id', $conversationId)->exists()) return null;

        $csatRating = CsatRating::create([
            'team_id'         => $conversation->team_id,
            'conversation_id' => $conversation->id,
            'contact_id'      => $contactId,
            'agent_id'        => $conversation->assigned_to,
            'rating'          => (int) $rating,
            'type'            => 'csat',
        ]);

        // Send a thank-you follow-up
        $this->sendThankYou($conversation, (int) $rating);

        // Alert supervisor if rating is low (1 or 2)
        if ((int) $rating <= 2) {
            $this->alertLowRating($conversation, (int) $rating);
        }

        return $csatRating;
    }

    public function record(Conversation $conversation, int $rating, ?string $feedback = null): CsatRating
    {
        return CsatRating::create([
            'team_id'         => $conversation->team_id,
            'conversation_id' => $conversation->id,
            'contact_id'      => $conversation->contact_id,
            'agent_id'        => $conversation->assigned_to,
            'rating'          => $rating,
            'feedback'        => $feedback,
            'type'            => 'csat',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Analytics helpers
    // ─────────────────────────────────────────────────────────────────────────
    public function getTeamStats(Team $team, int $days = 30): array
    {
        $ratings = CsatRating::where('team_id', $team->id)
            ->where('created_at', '>=', now()->subDays($days))
            ->get();

        if ($ratings->isEmpty()) {
            return ['avg' => null, 'count' => 0, 'distribution' => [], 'trend' => []];
        }

        $distribution = $ratings->groupBy('rating')->map->count()->toArray();
        $avg          = round($ratings->avg('rating'), 2);

        // Weekly trend (last 4 weeks)
        $trend = [];
        for ($w = 3; $w >= 0; $w--) {
            $start = now()->startOfWeek()->subWeeks($w);
            $end   = $start->copy()->endOfWeek();
            $week  = $ratings->filter(fn($r) => $r->created_at->between($start, $end));
            $trend[] = [
                'week'  => $start->format('d M'),
                'avg'   => $week->isNotEmpty() ? round($week->avg('rating'), 1) : null,
                'count' => $week->count(),
            ];
        }

        return [
            'avg'          => $avg,
            'count'        => $ratings->count(),
            'distribution' => $distribution,
            'trend'        => $trend,
        ];
    }

    public function getAgentStats(Team $team, int $days = 30): array
    {
        return CsatRating::where('team_id', $team->id)
            ->where('created_at', '>=', now()->subDays($days))
            ->whereNotNull('agent_id')
            ->with('agent:id,name')
            ->get()
            ->groupBy('agent_id')
            ->map(function ($agentRatings) {
                $agent = $agentRatings->first()->agent;
                return [
                    'agent_id'   => $agent?->id,
                    'agent_name' => $agent?->name ?? 'Unknown',
                    'avg_rating' => round($agentRatings->avg('rating'), 2),
                    'count'      => $agentRatings->count(),
                ];
            })
            ->sortByDesc('avg_rating')
            ->values()
            ->toArray();
    }

    private function sendThankYou(Conversation $conversation, int $rating): void
    {
        $contact = $conversation->contact;
        $team    = $conversation->team;
        $emoji   = $rating >= 4 ? '🌟' : ($rating === 3 ? '🙏' : '😔');
        $msg     = $rating >= 4
            ? "Thank you for the {$rating}/5 rating, {$contact->name}! {$emoji} Your feedback motivates our team."
            : "Thank you for your feedback, {$contact->name}. {$emoji} We're sorry your experience wasn't perfect and will work to improve.";

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $contact->phone_number,
            'type'              => 'text',
            'text'              => ['body' => $msg],
        ];

        try {
            $this->whatsapp->sendRaw($team, $payload);
        } catch (\Exception $e) {
            Log::warning("[CSAT] Thank-you send failed: " . $e->getMessage());
        }
    }

    private function alertLowRating(Conversation $conversation, int $rating): void
    {
        $team  = $conversation->team;
        $owner = $team->owner;
        if (! $owner) return;

        $owner->notify(new \App\Notifications\WhatsAppHealthNotification(
            $team,
            'low_csat',
            "Low CSAT Alert: {$conversation->contact->name} rated {$rating}/5 ⚠️. Conversation #{$conversation->id}.",
            ['conversation_id' => $conversation->id, 'rating' => $rating]
        ));
    }
}
