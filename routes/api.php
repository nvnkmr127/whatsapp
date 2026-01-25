<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/webhooks/trigger/{id}', [\App\Http\Controllers\Api\WebhookTriggerController::class, 'trigger'])->middleware('auth:sanctum');

Route::group(['middleware' => ['auth:sanctum', 'throttle:api'], 'prefix' => 'v1'], function () {
    // Contacts
    Route::get('/contacts', [\App\Http\Controllers\Api\ExternalContactController::class, 'index']);
    Route::post('/contacts', [\App\Http\Controllers\Api\ExternalContactController::class, 'store']);

    // Templates
    Route::get('/templates', [\App\Http\Controllers\Api\ExternalTemplateController::class, 'index']);

    // Conversations
    Route::get('/conversations/{phone}', [\App\Http\Controllers\ExternalConversationController::class, 'index']);

    // Messages
    Route::post('/messages', [\App\Http\Controllers\ExternalConversationController::class, 'send']);

    // OTP Verification
    Route::post('/otp/verify', [\App\Http\Controllers\Api\OTPVerificationController::class, 'verify']);

    // Inbound Webhooks (receive from external software)
    Route::post('/webhooks/inbound', [\App\Http\Controllers\Api\InboundWebhookController::class, 'handle']);
    Route::get('/webhooks/inbound/url', [\App\Http\Controllers\Api\InboundWebhookController::class, 'getUrl']);

    // Source-specific webhook endpoints (no auth required - verified by source config)
    Route::post('/webhooks/inbound/{source}', [\App\Http\Controllers\Api\InboundWebhookController::class, 'handleSource'])->withoutMiddleware(['auth:sanctum']);
    Route::get('/webhooks/sources/{source}/url', [\App\Http\Controllers\Api\InboundWebhookController::class, 'getSourceUrl']);

    // Embed Token (if needed)
    Route::post('/embed-token', [\App\Http\Controllers\EmbedController::class, 'generateToken']);

    // Conversation Locks (Multi-Agent)


    // Inbox Contact Integration
    Route::prefix('inbox/contacts')->group(function () {
        Route::get('resolve', [\App\Http\Controllers\Api\InboxContactController::class, 'resolve']);
        Route::post('resolve-batch', [\App\Http\Controllers\Api\InboxContactController::class, 'resolveBatch']);
        Route::put('{contact}', [\App\Http\Controllers\Api\InboxContactController::class, 'update']);
        Route::post('{contact}/assign', [\App\Http\Controllers\Api\InboxContactController::class, 'assign']);
    });

    // Ecommerce Integrations Management
    Route::prefix('ecommerce/integrations')->group(function () {
        Route::get('{integration}/health', [\App\Http\Controllers\Api\EcommerceIntegrationController::class, 'health']);
        Route::post('{integration}/sync', [\App\Http\Controllers\Api\EcommerceIntegrationController::class, 'sync']);
        Route::get('{integration}/sessions', [\App\Http\Controllers\Api\EcommerceIntegrationController::class, 'sessions']);
        Route::patch('{integration}/settings', [\App\Http\Controllers\Api\EcommerceIntegrationController::class, 'updateSettings']);
    });

    // Product Customization
    Route::post('/products/{product}/lock', [\App\Http\Controllers\Api\EcommerceIntegrationController::class, 'lockField']);
});

use App\Http\Controllers\WhatsAppWebhookController;

Route::get('/webhook/whatsapp', [WhatsAppWebhookController::class, 'verify'])->name('api.webhook.whatsapp');
Route::post('/webhook/whatsapp', [WhatsAppWebhookController::class, 'handle'])
    ->middleware(\App\Http\Middleware\VerifyWhatsAppSignature::class);
Route::post('/whatsapp/flow', [App\Http\Controllers\WhatsAppFlowController::class, 'handle']);

// Commerce Webhooks
Route::post('/webhooks/shopify/orders', [\App\Http\Controllers\Webhooks\ShopifyWebhookController::class, 'handle']);
Route::post('/webhooks/woocommerce/orders', [\App\Http\Controllers\Webhooks\WooCommerceWebhookController::class, 'handle']);
Route::post('/webhooks/custom/orders', [\App\Http\Controllers\Webhooks\CustomSiteWebhookController::class, 'handle']);

// Meta Commerce Webhooks
Route::get('/webhooks/meta/commerce', [\App\Http\Controllers\Webhooks\MetaCommerceWebhookController::class, 'verify']);
Route::post('/webhooks/meta/commerce', [\App\Http\Controllers\Webhooks\MetaCommerceWebhookController::class, 'handle']);
