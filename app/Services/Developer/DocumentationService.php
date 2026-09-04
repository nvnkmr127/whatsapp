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
                        'description' => 'Fetch all contacts with active opt-in status and custom attributes. Results are paginated (50 per page); use ?page=N to page through them.',
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
                                'referred_by' => 'partner_a',
                            ],
                            'opt_in' => true,
                        ],
                    ],
                ],
            ],
            [
                'id' => 'messages',
                'title' => 'Conversational Messaging',
                'endpoint' => 'v1/messages',
                'methods' => [
                    [
                        'verb' => 'POST',
                        'path' => '/messages',
                        'description' => 'Send text or template messages. Accepts Meta-style fields ("to", "text.body", "template") or flat fields ("phone_number", "message", "template_name"). Send an "X-Idempotency-Key" header to safely retry a request without sending twice. Returns 202 Accepted; the message is queued and delivered asynchronously.',
                        'examples' => [
                            [
                                'label' => 'Standard Text',
                                'json' => [
                                    'to' => '+1...',
                                    'type' => 'text',
                                    'text' => ['body' => 'Protocol engaged.'],
                                ],
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
                                                'parameters' => [['type' => 'text', 'text' => '8812']],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'id' => 'conversations',
                'title' => 'Conversation History',
                'endpoint' => 'v1/conversations',
                'methods' => [
                    [
                        'verb' => 'GET',
                        'path' => '/conversations/{phone}',
                        'description' => 'Retrieve the 50 most recent messages exchanged with a contact. The phone number may include a leading "+" or not. Returns an empty data array if the contact or conversation does not exist.',
                        'curl' => 'curl -X GET "{base_url}/conversations/+1234567890" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"',
                    ],
                ],
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
                    ],
                    [
                        'verb' => 'GET',
                        'path' => '/templates/{id}',
                        'description' => 'Retrieve a single template. The {id} may be the internal id, the Meta template id, or the template name.',
                        'curl' => 'curl -X GET "{base_url}/templates/order_confirmation" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"',
                    ],
                ],
            ],
            [
                'id' => 'otp',
                'title' => 'OTP Verification',
                'endpoint' => 'v1/otp',
                'methods' => [
                    [
                        'verb' => 'POST',
                        'path' => '/otp/verify',
                        'description' => 'Verify a one-time passcode previously delivered to a contact. Accepts "phone"/"code" or the aliases "phone_number"/"otp". Returns 422 if the code is invalid or expired.',
                        'body' => [
                            'phone' => '+1234567890',
                            'code' => '123456',
                        ],
                    ],
                ],
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
