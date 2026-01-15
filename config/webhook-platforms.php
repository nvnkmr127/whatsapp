<?php

return [
    'shopify' => [
        'name' => 'Shopify',
        'auth_method' => 'hmac',
        'auth_config' => [
            'header' => 'X-Shopify-Hmac-SHA256',
            'algorithm' => 'sha256',
        ],
        'event_type_path' => 'topic',
        'sample_mappings' => [
            'orders/create' => [
                'phone_number' => 'customer.phone',
                'customer_name' => 'customer.first_name',
                'customer_email' => 'customer.email',
                'order_id' => 'order_number',
                'order_total' => 'total_price',
                'currency' => 'currency',
            ],
            'orders/cancelled' => [
                'phone_number' => 'customer.phone',
                'customer_name' => 'customer.first_name',
                'order_id' => 'order_number',
                'cancel_reason' => 'cancel_reason',
            ],
        ],
        'sample_transformations' => [
            'phone_number' => 'format_phone',
            'order_total' => 'to_float',
        ],
    ],

    'stripe' => [
        'name' => 'Stripe',
        'auth_method' => 'hmac',
        'auth_config' => [
            'header' => 'Stripe-Signature',
            'algorithm' => 'sha256',
        ],
        'event_type_path' => 'type',
        'sample_mappings' => [
            'payment_intent.succeeded' => [
                'phone_number' => 'data.object.customer_details.phone',
                'customer_name' => 'data.object.customer_details.name',
                'customer_email' => 'data.object.customer_details.email',
                'amount' => 'data.object.amount',
                'currency' => 'data.object.currency',
                'payment_id' => 'data.object.id',
            ],
            'payment_intent.payment_failed' => [
                'phone_number' => 'data.object.customer_details.phone',
                'customer_name' => 'data.object.customer_details.name',
                'amount' => 'data.object.amount',
                'currency' => 'data.object.currency',
                'error_message' => 'data.object.last_payment_error.message',
            ],
        ],
        'sample_transformations' => [
            'amount' => 'stripe_amount_to_decimal',
            'phone_number' => 'format_phone',
        ],
    ],

    'woocommerce' => [
        'name' => 'WooCommerce',
        'auth_method' => 'hmac',
        'auth_config' => [
            'header' => 'X-WC-Webhook-Signature',
            'algorithm' => 'sha256',
        ],
        'event_type_path' => 'topic',
        'sample_mappings' => [
            'order.created' => [
                'phone_number' => 'billing.phone',
                'customer_name' => 'billing.first_name',
                'customer_email' => 'billing.email',
                'order_id' => 'number',
                'order_total' => 'total',
                'currency' => 'currency',
            ],
        ],
        'sample_transformations' => [
            'phone_number' => 'format_phone',
            'order_total' => 'to_float',
        ],
    ],

    'zapier' => [
        'name' => 'Zapier',
        'auth_method' => 'api_key',
        'auth_config' => [
            'header' => 'X-API-Key',
        ],
        'event_type_path' => 'event',
        'sample_mappings' => [
            'custom' => [
                'phone_number' => 'phone',
                'customer_name' => 'name',
                'customer_email' => 'email',
            ],
        ],
        'sample_transformations' => [
            'phone_number' => 'format_phone',
        ],
    ],

    'make' => [
        'name' => 'Make (Integromat)',
        'auth_method' => 'api_key',
        'auth_config' => [
            'header' => 'X-API-Key',
        ],
        'event_type_path' => 'event',
        'sample_mappings' => [
            'custom' => [
                'phone_number' => 'phone',
                'customer_name' => 'name',
                'customer_email' => 'email',
            ],
        ],
        'sample_transformations' => [
            'phone_number' => 'format_phone',
        ],
    ],

    'custom' => [
        'name' => 'Custom Integration',
        'auth_method' => 'api_key',
        'auth_config' => [
            'header' => 'X-API-Key',
        ],
        'event_type_path' => 'event',
        'sample_mappings' => [],
        'sample_transformations' => [],
    ],
];
