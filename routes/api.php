<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/webhooks/trigger/{id}', [\App\Http\Controllers\Api\WebhookTriggerController::class, 'trigger'])->middleware('auth:sanctum');
Route::post('/webhooks/workflow/{workflowId}', [\App\Http\Controllers\Api\WorkflowWebhookController::class, 'handle']);
Route::post('/webhooks/workflow-incoming/{webhookUrlId}', [\App\Http\Controllers\Api\WorkflowIncomingWebhookController::class, 'handle']);

Route::group(['middleware' => ['auth:sanctum', 'tenant', 'throttle:api', \App\Http\Middleware\BlockTrialFieldsViaApi::class], 'prefix' => 'v1'], function () {
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

    // Conversation Locks (Multi-Agent) - Moved to web.php for Session Auth
    // Route::post('/conversations/{id}/lock', [\App\Http\Controllers\Api\ConversationLockController::class, 'lock']);
    // Route::post('/conversations/{id}/unlock', [\App\Http\Controllers\Api\ConversationLockController::class, 'unlock']);
    // Route::post('/conversations/{id}/takeover', [\App\Http\Controllers\Api\ConversationLockController::class, 'takeover']);
    // Route::post('/conversations/{id}/heartbeat', [\App\Http\Controllers\Api\ConversationLockController::class, 'heartbeat']);


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

    // Mobile Core
    Route::prefix('mobile')->group(function () {
        // FCM Tokens
        Route::post('/fcm/register', [\App\Http\Controllers\Api\Mobile\FCMTokenController::class, 'store']);
        Route::post('/fcm/remove', [\App\Http\Controllers\Api\Mobile\FCMTokenController::class, 'destroy']);

        // Presence (Active Chat State)
        Route::post('/presence/heartbeat', [\App\Http\Controllers\Api\Mobile\PresenceController::class, 'heartbeat']);
        Route::post('/presence/leave', [\App\Http\Controllers\Api\Mobile\PresenceController::class, 'leave']);

        // Inbox Management
        Route::get('/conversations', [\App\Http\Controllers\Api\Mobile\ConversationController::class, 'index']);
        Route::get('/conversations/{conversation}', [\App\Http\Controllers\Api\Mobile\ConversationController::class, 'show']);
        Route::post('/conversations/{conversation}/read', [\App\Http\Controllers\Api\Mobile\ConversationController::class, 'markAsRead']);
        Route::post('/conversations/{conversation}/assign', [\App\Http\Controllers\Api\Mobile\ConversationController::class, 'assign']);
        Route::post('/conversations/{conversation}/close', [\App\Http\Controllers\Api\Mobile\ConversationController::class, 'close']);

        // Chat Management
        Route::get('/conversations/{conversation}/messages', [\App\Http\Controllers\Api\Mobile\MessageController::class, 'index']);
        Route::post('/conversations/{conversation}/messages', [\App\Http\Controllers\Api\Mobile\MessageController::class, 'store']);
        Route::delete('/messages/{message}', [\App\Http\Controllers\Api\Mobile\MessageController::class, 'destroy']);
        Route::post('/messages/{message}/forward', [\App\Http\Controllers\Api\Mobile\MessageController::class, 'forward']);
        Route::post('/messages/{message}/star', [\App\Http\Controllers\Api\Mobile\MessageController::class, 'toggleStar']);
        Route::post('/messages/{message}/react', [\App\Http\Controllers\Api\Mobile\MessageController::class, 'react']);
        Route::get('/templates', [\App\Http\Controllers\Api\Mobile\MessageController::class, 'getTemplates']);
        Route::post('/conversations/{conversation}/send-template', [\App\Http\Controllers\Api\Mobile\MessageController::class, 'sendTemplate']);

        // Internal Notes
        Route::get('/conversations/{conversation}/notes', [\App\Http\Controllers\Api\Mobile\ConversationController::class, 'getNotes']);
        Route::post('/conversations/{conversation}/notes', [\App\Http\Controllers\Api\Mobile\ConversationController::class, 'storeNote']);

        // Canned Messages
        Route::get('/canned-messages', [\App\Http\Controllers\Api\Mobile\ConversationController::class, 'getCannedMessages']);

        // Media Uploads
        Route::post('/media/upload', [\App\Http\Controllers\Api\Mobile\MediaController::class, 'upload']);

        // Contacts
        Route::get('/contacts/tags', [\App\Http\Controllers\Api\Mobile\ContactController::class, 'getAvailableTags']);
        Route::get('/contacts/search', [\App\Http\Controllers\Api\Mobile\ContactController::class, 'search']);
        Route::get('/contacts/{contact}', [\App\Http\Controllers\Api\Mobile\ContactController::class, 'show']);
        Route::post('/contacts/{contact}', [\App\Http\Controllers\Api\Mobile\ContactController::class, 'update']);
        Route::post('/contacts/{contact}/toggle-tag', [\App\Http\Controllers\Api\Mobile\ContactController::class, 'toggleTag']);

        // Analytics
        Route::get('/analytics/dashboard', [\App\Http\Controllers\Api\Mobile\AnalyticsController::class, 'dashboard']);

        // Campaigns / Broadcasting
        Route::get('/campaigns', [\App\Http\Controllers\Api\Mobile\CampaignController::class, 'index']);
        Route::post('/campaigns', [\App\Http\Controllers\Api\Mobile\CampaignController::class, 'store']);
    });

    // WhatsApp Calling API
    Route::prefix('calls')->group(function () {
        Route::post('/initiate', [\App\Http\Controllers\Api\CallController::class, 'initiate']);
        Route::post('/check-eligibility', [\App\Http\Controllers\Api\CallController::class, 'checkEligibility']);
        Route::post('/{callId}/answer', [\App\Http\Controllers\Api\CallController::class, 'answer']);
        Route::post('/{callId}/reject', [\App\Http\Controllers\Api\CallController::class, 'reject']);
        Route::post('/{callId}/end', [\App\Http\Controllers\Api\CallController::class, 'end']);
        Route::get('/', [\App\Http\Controllers\Api\CallController::class, 'index']);
        Route::get('/active', [\App\Http\Controllers\Api\CallController::class, 'active']);
        Route::get('/statistics', [\App\Http\Controllers\Api\CallController::class, 'statistics']);
        Route::get('/{callId}', [\App\Http\Controllers\Api\CallController::class, 'show']);
        Route::get('/contacts/{contactId}/history', [\App\Http\Controllers\Api\CallController::class, 'contactHistory']);
    });

    // WhatsApp Call Settings & Permissions
    Route::prefix('whatsapp')->group(function () {
        // Call Settings
        Route::post('/{phoneNumberId}/settings', [\App\Http\Controllers\CallSettingsController::class, 'update']);
        Route::get('/{phoneNumberId}/settings', [\App\Http\Controllers\CallSettingsController::class, 'show']);

        // Call Permissions
        Route::post('/calls/request-permission', [\App\Http\Controllers\CallPermissionController::class, 'requestPermission']);
        Route::get('/calls/permission/{contactId}', [\App\Http\Controllers\CallPermissionController::class, 'checkPermission']);
        Route::post('/calls/initiate', [\App\Http\Controllers\CallPermissionController::class, 'initiateCall']);

        // Call Links
        Route::post('/calls/generate-link', [\App\Http\Controllers\CallSettingsController::class, 'generateLink']);
    });

});

use App\Http\Controllers\WhatsAppWebhookController;

Route::get('/webhook/whatsapp', [WhatsAppWebhookController::class, 'verify'])->name('api.webhook.whatsapp');
Route::post('/webhook/whatsapp', [WhatsAppWebhookController::class, 'handle'])
    ->middleware(\App\Http\Middleware\VerifyWhatsAppSignature::class);

// WhatsApp Calling Webhooks
Route::get('/webhook/whatsapp/calls', [\App\Http\Controllers\Webhooks\WhatsAppCallWebhookController::class, 'verify']);
Route::post('/webhook/whatsapp/calls', [\App\Http\Controllers\Webhooks\WhatsAppCallWebhookController::class, 'handle']);

Route::post('/whatsapp/flow', [App\Http\Controllers\WhatsAppFlowController::class, 'handle']);

// Commerce Webhooks
Route::post('/webhooks/shopify/orders', [\App\Http\Controllers\Webhooks\ShopifyWebhookController::class, 'handle']);
Route::post('/webhooks/woocommerce/orders', [\App\Http\Controllers\Webhooks\WooCommerceWebhookController::class, 'handle']);
Route::post('/webhooks/custom/orders', [\App\Http\Controllers\Webhooks\CustomSiteWebhookController::class, 'handle']);

// Meta Commerce Webhooks
Route::get('/webhooks/meta/commerce', [\App\Http\Controllers\Webhooks\MetaCommerceWebhookController::class, 'verify']);
Route::post('/webhooks/meta/commerce', [\App\Http\Controllers\Webhooks\MetaCommerceWebhookController::class, 'handle']);

// Email Provider Webhooks
Route::post('/webhooks/email/{provider}', [\App\Http\Controllers\Webhooks\Email\EmailWebhookController::class, 'handle']);
