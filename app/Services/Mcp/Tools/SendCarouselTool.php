<?php

namespace App\Services\Mcp\Tools;

use App\Models\Team;
use App\Services\Mcp\McpTool;
use App\Services\OutboundPreflightService;
use App\Services\WhatsAppService;

class SendCarouselTool implements McpTool
{
    public function schema(): array
    {
        return [
            'name'        => 'send_carousel',
            'description' => 'Send a horizontally scrollable carousel of cards. Useful for displaying products, options, or articles.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'phone' => ['type' => 'string', 'description' => 'Contact phone number in E.164 format'],
                    'cards' => [
                        'type' => 'array',
                        'description' => 'Array of up to 10 cards to display in the carousel',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'title'       => ['type' => 'string', 'maxLength' => 80],
                                'body'        => ['type' => 'string', 'maxLength' => 80],
                                'image_url'   => ['type' => 'string', 'description' => 'Optional image URL for the card header'],
                                'video_url'   => ['type' => 'string', 'description' => 'Optional video URL for the card header'],
                                'buttons'     => [
                                    'type' => 'array',
                                    'description' => 'Up to 3 buttons per card',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'type'  => ['type' => 'string', 'enum' => ['reply', 'url', 'call']],
                                            'title' => ['type' => 'string', 'maxLength' => 20],
                                            'payload' => ['type' => 'string', 'description' => 'For reply type'],
                                            'url'     => ['type' => 'string', 'description' => 'For url type'],
                                            'phone'   => ['type' => 'string', 'description' => 'For call type']
                                        ],
                                        'required' => ['type', 'title']
                                    ]
                                ]
                            ],
                            'required' => ['title', 'buttons']
                        ]
                    ]
                ],
                'required' => ['phone', 'cards'],
            ],
        ];
    }

    public function handle(array $input, Team $team): array
    {
        $phone = \App\Helpers\PhoneNumberHelper::normalize($input['phone']);
        $cards = $input['cards'];

        if (count($cards) > 10) {
            return [['type' => 'text', 'text' => 'Error: WhatsApp carousels can have a maximum of 10 cards.']];
        }

        // 1. Resolve contact
        $contact = \App\Models\Contact::firstOrCreate(
            ['team_id' => $team->id, 'phone_number' => $phone],
            ['name' => $phone]
        );

        // 2. Preflight (Blocklist, limits, billing)
        $preflight = app(OutboundPreflightService::class);
        $check = $preflight->check($team, $contact, 'carousel');
        if (! $check['allowed']) {
            return [['type' => 'text', 'text' => "Preflight failed: {$check['reason']}"]];
        }

        // 3. Ensure active conversation
        $conversation = app(\App\Services\ConversationService::class)->ensureActiveConversation($contact);

        // 4. Transform cards for WhatsAppService
        $formattedCards = [];
        foreach ($cards as $card) {
            $formattedCard = [
                'title' => $card['title'],
                'body'  => $card['body'] ?? '',
                'buttons' => []
            ];

            if (!empty($card['image_url'])) {
                $formattedCard['header'] = ['type' => 'image', 'link' => $card['image_url']];
            } elseif (!empty($card['video_url'])) {
                $formattedCard['header'] = ['type' => 'video', 'link' => $card['video_url']];
            }

            foreach ($card['buttons'] as $btn) {
                if ($btn['type'] === 'reply') {
                    $formattedCard['buttons'][] = ['type' => 'reply', 'reply' => ['id' => $btn['payload'] ?? $btn['title'], 'title' => $btn['title']]];
                } elseif ($btn['type'] === 'url') {
                    $formattedCard['buttons'][] = ['type' => 'url', 'url' => $btn['url'], 'title' => $btn['title']];
                } elseif ($btn['type'] === 'call') {
                    $formattedCard['buttons'][] = ['type' => 'call', 'phone_number' => $btn['phone'], 'title' => $btn['title']];
                }
            }

            $formattedCards[] = $formattedCard;
        }

        try {
            $message = app(WhatsAppService::class)->sendCarousel($phone, $formattedCards);
            $message->update(['conversation_id' => $conversation->id, 'team_id' => $team->id]);

            return [['type' => 'text', 'text' => "Carousel sent successfully. Message ID: {$message->id}"]];
        } catch (\Exception $e) {
            return [['type' => 'text', 'text' => 'Error sending carousel: ' . $e->getMessage()]];
        }
    }
}
