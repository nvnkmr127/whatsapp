<?php

namespace App\Services\Developer;

class DocumentationService
{
    public function getApiSections(): array
    {
        return [
            [
                'id' => 'intro',
                'title' => 'WhatsApp Business API',
                'description' => 'Our RESTful API allows you to programmatically manage contacts, send messages, initiate WhatsApp calls, connect AI agents via MCP, and integrate WhatsApp messaging into your CRMs, dashboards, and e-commerce platforms.',
                'is_main' => true,
            ],
            [
                'id' => 'auth',
                'title' => 'Secure Authentication & Headers',
                'description' => 'All API requests require authentication using Bearer tokens. If your user belongs to multiple teams or you are calling via API token, include the optional X-Tenant-ID header to target a specific workspace.',
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
                        'description' => 'Fetch all contacts with active opt-in status, tags, and custom attributes. Results are paginated (50 per page); use ?page=N to page through them.',
                        'curl' => 'curl -X GET "{base_url}/contacts?page=1" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"',
                    ],
                    [
                        'verb' => 'POST',
                        'path' => '/contacts',
                        'description' => 'Create or update a contact. Safely merges custom attributes and preserves existing email if omitted. Automatically logs opt-in consent if requested.',
                        'body' => [
                            'phone_number' => '+1234567890',
                            'name' => 'Jane Wilson',
                            'email' => 'jane@company.com',
                            'custom_attributes' => [
                                'tier' => 'enterprise',
                                'company' => 'Acme Corp',
                            ],
                            'opt_in' => true,
                            'opt_in_source' => 'API',
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
                        'description' => 'Send text or approved template messages. Accepts Meta-style payload envelopes ("to", "text.body", "template") or flat fields ("phone_number", "message", "template_name"). Send an "X-Idempotency-Key" header to safely retry requests without duplicate delivery. Returns HTTP 202 with status "queued", "message_id", and "conversation_id".',
                        'examples' => [
                            [
                                'label' => 'Standard Text',
                                'json' => [
                                    'to' => '+1234567890',
                                    'type' => 'text',
                                    'text' => ['body' => 'Hello from WhatsApp API!'],
                                ],
                            ],
                            [
                                'label' => 'WhatsApp Template',
                                'json' => [
                                    'to' => '+1234567890',
                                    'type' => 'template',
                                    'template' => [
                                        'name' => 'order_update',
                                        'language' => ['code' => 'en_US'],
                                        'components' => [
                                            [
                                                'type' => 'body',
                                                'parameters' => [
                                                    ['type' => 'text', 'text' => 'Order #9821'],
                                                    ['type' => 'text', 'text' => 'Shipped'],
                                                ],
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
                        'description' => 'Retrieve the 50 most recent messages exchanged with a contact. Automatically resolves the active conversation thread. The phone number accepts formats with or without leading "+".',
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
                        'description' => 'Retrieve all approved WhatsApp message templates for your workspace. Results are cached locally with fallback to live Meta Cloud API. Pass ?force_refresh=1 to bypass cache.',
                        'curl' => 'curl -X GET "{base_url}/templates" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"',
                    ],
                    [
                        'verb' => 'GET',
                        'path' => '/templates/{id}',
                        'description' => 'Retrieve a single template by internal ID, WhatsApp template ID, or template name.',
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
                        'description' => 'Verify a one-time passcode delivered to a contact phone number. Enforces tenant isolation, rate limiting (max 5 attempts), and 5-minute TTL. Returns HTTP 422 if invalid or expired.',
                        'body' => [
                            'phone' => '+1234567890',
                            'code' => '543210',
                        ],
                    ],
                ],
            ],
            [
                'id' => 'calls',
                'title' => 'WhatsApp Calling API',
                'endpoint' => 'v1/calls',
                'methods' => [
                    [
                        'verb' => 'POST',
                        'path' => '/calls/initiate',
                        'description' => 'Initiate an outbound WhatsApp VoIP call to a customer phone number with WebRTC SDP offer negotiation and preflight billing checks.',
                        'body' => [
                            'phone_number' => '+1234567890',
                            'sdp' => 'v=0\r\no=- 12345 2 IN IP4 127.0.0.1...',
                            'options' => [
                                'record' => false,
                            ],
                        ],
                    ],
                    [
                        'verb' => 'POST',
                        'path' => '/calls/check-eligibility',
                        'description' => 'Check if a contact is eligible to receive outbound WhatsApp calls based on customer consent window and business hours.',
                        'body' => [
                            'contact_id' => 124,
                        ],
                    ],
                    [
                        'verb' => 'GET',
                        'path' => '/calls',
                        'description' => 'Retrieve paginated call history with optional filters for direction (inbound/outbound), status (completed, failed, busy), and date ranges.',
                        'curl' => 'curl -X GET "{base_url}/calls?direction=outbound&per_page=20" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"',
                    ],
                    [
                        'verb' => 'GET',
                        'path' => '/calls/{callId}',
                        'description' => 'Get full metadata for a specific call: duration, timestamps, billing cost, and failure reason if applicable.',
                        'curl' => 'curl -X GET "{base_url}/calls/CALL_ABC123" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"',
                    ],
                    [
                        'verb' => 'GET',
                        'path' => '/calls/statistics',
                        'description' => 'Get call metrics, total duration, and current monthly calling allowance usage. Supports period: today, week, month, year.',
                        'curl' => 'curl -X GET "{base_url}/calls/statistics?period=month" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"',
                    ],
                    [
                        'verb' => 'POST',
                        'path' => '/calls/{callId}/end',
                        'description' => 'End an ongoing active WhatsApp call.',
                        'curl' => 'curl -X POST "{base_url}/calls/CALL_ABC123/end" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"',
                    ],
                ],
            ],
            [
                'id' => 'mcp',
                'title' => 'MCP (Model Context Protocol)',
                'endpoint' => 'v1/mcp',
                'methods' => [
                    [
                        'verb' => 'POST',
                        'path' => '/mcp',
                        'description' => 'Streamable Model Context Protocol HTTP endpoint (JSON-RPC 2.0). Connect Cursor, Claude Desktop, or custom AI agents to query contacts, send messages, and execute tools within your tenant boundary.',
                        'examples' => [
                            [
                                'label' => 'tools/list',
                                'json' => [
                                    'jsonrpc' => '2.0',
                                    'id' => 1,
                                    'method' => 'tools/list',
                                ],
                            ],
                            [
                                'label' => 'tools/call (Send Message)',
                                'json' => [
                                    'jsonrpc' => '2.0',
                                    'id' => 2,
                                    'method' => 'tools/call',
                                    'params' => [
                                        'name' => 'send_whatsapp_message',
                                        'arguments' => [
                                            'phone_number' => '+1234567890',
                                            'message' => 'Your reservation is confirmed!',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'id' => 'embed',
                'title' => 'Embedded Chat Widget Tokens',
                'endpoint' => 'v1/embed-token',
                'methods' => [
                    [
                        'verb' => 'POST',
                        'path' => '/embed-token',
                        'description' => 'Generate a signed, short-lived JWT embed token to embed a WhatsApp conversation window directly into your external CRM or web application.',
                        'body' => [
                            'phone_number' => '+1234567890',
                            'name' => 'Jane Wilson',
                            'permissions' => ['read', 'write'],
                        ],
                    ],
                ],
            ],
            [
                'id' => 'concurrency',
                'title' => 'Conversation Locks (Multi-Agent Concurrency)',
                'endpoint' => 'v1/conversations/{id}',
                'methods' => [
                    [
                        'verb' => 'POST',
                        'path' => '/conversations/{id}/lock',
                        'description' => 'Acquire an exclusive lock on a conversation (30s TTL) to prevent collision with other human or AI agents.',
                        'curl' => 'curl -X POST "{base_url}/conversations/102/lock" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"',
                    ],
                    [
                        'verb' => 'POST',
                        'path' => '/conversations/{id}/heartbeat',
                        'description' => 'Renew lock TTL for another 30 seconds while an agent is actively viewing or typing.',
                        'curl' => 'curl -X POST "{base_url}/conversations/102/heartbeat" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"',
                    ],
                    [
                        'verb' => 'POST',
                        'path' => '/conversations/{id}/unlock',
                        'description' => 'Release an active conversation lock.',
                        'curl' => 'curl -X POST "{base_url}/conversations/102/unlock" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"',
                    ],
                ],
            ],
            [
                'id' => 'inbox-contacts',
                'title' => 'Inbox Contact Resolution',
                'endpoint' => 'v1/inbox/contacts',
                'methods' => [
                    [
                        'verb' => 'GET',
                        'path' => '/inbox/contacts/resolve',
                        'description' => 'Resolve contact record and identity details for an incoming customer phone number.',
                        'curl' => 'curl -X GET "{base_url}/inbox/contacts/resolve?phone=+1234567890" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"',
                    ],
                    [
                        'verb' => 'POST',
                        'path' => '/inbox/contacts/resolve-batch',
                        'description' => 'Batch resolve multiple phone numbers into contact records in a single query.',
                        'body' => [
                            'phones' => ['+1234567890', '+1987654321'],
                        ],
                    ],
                    [
                        'verb' => 'PUT / PATCH',
                        'path' => '/inbox/contacts/{contact}',
                        'description' => 'Update contact with optimistic concurrency locking (requires current "version" integer to detect conflicts).',
                        'body' => [
                            'version' => 3,
                            'name' => 'Jane Smith',
                            'custom_attributes' => ['tier' => 'VIP'],
                        ],
                    ],
                ],
            ],
            [
                'id' => 'ecommerce',
                'title' => 'E-Commerce Integrations',
                'endpoint' => 'v1/ecommerce/integrations',
                'methods' => [
                    [
                        'verb' => 'GET',
                        'path' => '/ecommerce/integrations/{integration}/health',
                        'description' => 'Check health status and connection credentials for Shopify or WooCommerce store integrations.',
                        'curl' => 'curl -X GET "{base_url}/ecommerce/integrations/5/health" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"',
                    ],
                    [
                        'verb' => 'POST',
                        'path' => '/ecommerce/integrations/{integration}/sync',
                        'description' => 'Trigger an immediate product catalog synchronization for an e-commerce integration.',
                        'curl' => 'curl -X POST "{base_url}/ecommerce/integrations/5/sync" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"',
                    ],
                ],
            ],
            [
                'id' => 'inbound-sources',
                'title' => 'Advanced Inbound Webhooks',
                'description' => 'Connect Shopify, Stripe, WooCommerce, or your Custom Infrastructure. Each Source generates a unique cryptographic endpoint with visual field mapping logic.',
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
