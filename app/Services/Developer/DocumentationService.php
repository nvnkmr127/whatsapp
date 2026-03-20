<?php

namespace App\Services\Developer;

class DocumentationService
{
    public function getApiSections()
    {
        return [
            [
                'id' => 'intro',
                'title' => 'WhatsApp Business API',
                'description' => 'Our RESTful API allows you to programmatically manage contacts, send messages, and integrate WhatsApp messaging into your existing applications, CRMs, and e-commerce platforms.',
                'is_main' => true,
            ],
            [
                'id' => 'auth',
                'title' => 'Secure Authentication',
                'description' => 'All API requests require authentication using Bearer tokens. Tokens are managed per user and team.',
                'type' => 'auth',
            ],
            [
                'id' => 'contacts',
                'title' => 'Identity & Audience',
                'endpoint' => 'v1/contacts',
                'methods' => [
                    [
                        'verb' => 'GET',
                        'path' => '/contacts',
                        'description' => 'Fetch all contacts with active opt-in status and custom attributes.',
                        'curl' => 'curl -X GET "{base_url}/contacts" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"',
                    ],
                    [
                        'verb' => 'POST',
                        'path' => '/contacts',
                        'description' => 'Sync identities with your system. Automatically handles opt-in logging.',
                        'body' => [
                            'phone_number' => '+1234567890',
                            'name' => 'Jane Wilson',
                            'email' => 'jane@company.com',
                            'custom_attributes' => [
                                'tier' => 'enterprise',
                                'referred_by' => 'partner_a'
                            ],
                            'opt_in' => true
                        ]
                    ]
                ]
            ],
            [
                'id' => 'messages',
                'title' => 'Conversational Messaging',
                'endpoint' => 'v1/messages',
                'methods' => [
                    [
                        'verb' => 'POST',
                        'path' => '/messages',
                        'description' => 'Send high-impact text or template messages.',
                        'examples' => [
                            [
                                'label' => 'Standard Text',
                                'json' => [
                                    'to' => '+1...',
                                    'type' => 'text',
                                    'text' => ['body' => 'Protocol engaged.']
                                ]
                            ],
                            [
                                'label' => 'Marketing Template',
                                'json' => [
                                    'to' => '+1...',
                                    'type' => 'template',
                                    'template' => [
                                        'name' => 'otp_delivery',
                                        'language' => ['code' => 'en_US'],
                                        'components' => [
                                            [
                                                'type' => 'body',
                                                'parameters' => [['type' => 'text', 'text' => '8812']]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            [
                'id' => 'templates',
                'title' => 'Message Templates',
                'endpoint' => 'v1/templates',
                'methods' => [
                    [
                        'verb' => 'GET',
                        'path' => '/templates',
                        'description' => 'Retrieve a list of all approved WhatsApp message templates for your account.',
                        'curl' => 'curl -X GET "{base_url}/templates" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"',
                    ]
                ]
            ],
            [
                'id' => 'inbound-sources',
                'title' => 'Advanced Inbound Webhooks',
                'description' => 'Connect Shopify, Stripe, or your Custom Infrastructure. Each Source generates a unique cryptographic endpoint with visual field mapping logic.',
                'features' => [
                    ['title' => 'Security', 'description' => 'Supports HMAC, API-KEY, and Basic Auth validation.', 'icon' => 'lock'],
                    ['title' => 'Mapping', 'description' => 'Visually map JSON payloads to WhatsApp Templates.', 'icon' => 'map'],
                    ['title' => 'Delays', 'description' => 'Configure human-like processing delays (0-60m).', 'icon' => 'clock'],
                ],
                'example_endpoint' => '/api/v1/webhooks/inbound/{slug}',
                'example_curl' => 'curl -X POST "{base_url}/webhooks/inbound/shopify-orders" \
  -H "X-API-Key: YOUR_SOURCE_SECRET" \
  -H "Content-Type: application/json" \
  -d \'{"order_id": "9912", "phone": "1234567890"}\'',
            ],
        ];
    }
}
