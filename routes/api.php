<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/webhooks/trigger/{id}', [\App\Http\Controllers\Api\WebhookTriggerController::class, 'trigger'])->middleware('auth:sanctum');
Route::post('/webhooks/workflow/{workflowId}', [\App\Http\Controllers\Api\WorkflowWebhookController::class, 'handle'])->middleware('auth:sanctum');
Route::post('/webhooks/workflow-incoming/{webhookUrlId}', [\App\Http\Controllers\Api\WorkflowIncomingWebhookController::class, 'handle'])->middleware('auth:sanctum');

Route::prefix('v1/mobile/auth')->middleware('mobile_logger')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'login'])->middleware('throttle:mobile-auth');
    Route::post('/send-otp', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'sendOtp'])->middleware('throttle:mobile-auth');
    Route::post('/login-otp', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'loginWithOtp'])->middleware('throttle:mobile-auth');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'me']);
        Route::get('/teams', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'teams']);
        Route::get('/numbers', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'numbers']);
        Route::post('/finalize', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'finalize']);
        Route::post('/switch-team', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'switchTeam']);
        Route::post('/logout', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'logout']);
        Route::post('/profile', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'updateProfile']);
        Route::post('/fcm-token', [\App\Http\Controllers\Api\Mobile\FCMTokenController::class, 'store']);
        Route::post('/fcm-token/remove', [\App\Http\Controllers\Api\Mobile\FCMTokenController::class, 'destroy']);
    });
});

Route::group(['middleware' => ['auth:sanctum', 'tenant', 'throttle:api', \App\Http\Middleware\BlockTrialFieldsViaApi::class], 'prefix' => 'v1'], function () {
    // MCP (Model Context Protocol) — per-tenant AI agent endpoint
    Route::post('/mcp', [\App\Http\Controllers\Api\McpController::class, 'handle']);

    // Contacts
    Route::get('/contacts', [\App\Http\Controllers\Api\ExternalContactController::class, 'index']);
    Route::post('/contacts', [\App\Http\Controllers\Api\ExternalContactController::class, 'store']);

    // Templates
    Route::get('/templates', [\App\Http\Controllers\Api\ExternalTemplateController::class, 'index']);
    Route::get('/templates/{id}', [\App\Http\Controllers\Api\ExternalTemplateController::class, 'show']);

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

    // Conversation Locks (Multi-Agent) - Enabled for Sanctum Token Auth
    Route::post('/conversations/{id}/lock', [\App\Http\Controllers\Api\ConversationLockController::class, 'lock']);
    Route::post('/conversations/{id}/unlock', [\App\Http\Controllers\Api\ConversationLockController::class, 'unlock']);
    Route::post('/conversations/{id}/takeover', [\App\Http\Controllers\Api\ConversationLockController::class, 'takeover']);
    Route::post('/conversations/{id}/heartbeat', [\App\Http\Controllers\Api\ConversationLockController::class, 'heartbeat']);

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
    Route::prefix('mobile')->name('mobile.')->middleware('mobile_logger')->group(function () {
        // AI Assistant
        Route::get('/ai/settings', [\App\Http\Controllers\Api\Mobile\AiController::class, 'index']);
        Route::post('/ai/settings', [\App\Http\Controllers\Api\Mobile\AiController::class, 'update']);

        // Dashboard
        // Moved /auth/me, teams, numbers etc. to the non-tenant group above
        Route::post('/auth/switch-team', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'switchTeam'])->withoutMiddleware(['tenant']);

        // Presence (Active Chat State)
        Route::post('/presence/heartbeat', [\App\Http\Controllers\Api\Mobile\PresenceController::class, 'heartbeat']);
        Route::post('/presence/leave', [\App\Http\Controllers\Api\Mobile\PresenceController::class, 'leave']);

        Route::post('/broadcasting/auth', [\App\Http\Controllers\Api\Mobile\BroadcastAuthController::class, 'authenticate']);

        // Inbox Management
        Route::get('/conversations', [\App\Http\Controllers\Api\Mobile\ConversationController::class, 'index']);
        Route::get('/conversations/{conversation}', [\App\Http\Controllers\Api\Mobile\ConversationController::class, 'show']);
        Route::post('/conversations/{conversation}/read', [\App\Http\Controllers\Api\Mobile\ConversationController::class, 'markAsRead']);
        Route::post('/conversations/{conversation}/toggle-ai', [\App\Http\Controllers\Api\Mobile\ConversationController::class, 'toggleAi']);
        Route::post('/conversations/{conversation}/close', [\App\Http\Controllers\Api\Mobile\ConversationController::class, 'close']);
        Route::post('/conversations/{conversation}/reopen', [\App\Http\Controllers\Api\Mobile\ConversationController::class, 'reopen']);
        Route::post('/conversations/{conversation}/mark-read', [\App\Http\Controllers\Api\Mobile\ConversationController::class, 'markAsRead']);
        Route::post('/conversations/{conversation}/assign', [\App\Http\Controllers\Api\Mobile\ConversationController::class, 'assign']);
        Route::get('/canned-messages', [\App\Http\Controllers\Api\Mobile\ConversationController::class, 'getCannedMessages']);

        // Mobile Conversation Locks
        Route::post('/conversations/{id}/lock', [\App\Http\Controllers\Api\ConversationLockController::class, 'lock']);
        Route::post('/conversations/{id}/unlock', [\App\Http\Controllers\Api\ConversationLockController::class, 'unlock']);
        Route::post('/conversations/{id}/takeover', [\App\Http\Controllers\Api\ConversationLockController::class, 'takeover']);
        Route::post('/conversations/{id}/heartbeat', [\App\Http\Controllers\Api\ConversationLockController::class, 'heartbeat']);

        Route::get('/analytics/dashboard', [\App\Http\Controllers\Api\Mobile\AnalyticsController::class, 'dashboard']);
        Route::get('/messages/starred', [\App\Http\Controllers\Api\Mobile\MessageController::class, 'starred']);
        Route::get('/conversations/{conversation}/messages', [\App\Http\Controllers\Api\Mobile\MessageController::class, 'index']);
        Route::post('/conversations/{conversation}/messages', [\App\Http\Controllers\Api\Mobile\MessageController::class, 'store']);
        Route::delete('/messages/{message}', [\App\Http\Controllers\Api\Mobile\MessageController::class, 'destroy']);
        Route::post('/messages/{message}/forward', [\App\Http\Controllers\Api\Mobile\MessageController::class, 'forward']);
        Route::post('/messages/{message}/star', [\App\Http\Controllers\Api\Mobile\MessageController::class, 'toggleStar']);
        Route::post('/messages/{message}/react', [\App\Http\Controllers\Api\Mobile\MessageController::class, 'react']);
        Route::post('/conversations/{conversation}/send-template', [\App\Http\Controllers\Api\Mobile\MessageController::class, 'sendTemplate']);

        // Automations
        Route::apiResource('automations', \App\Http\Controllers\Api\Mobile\AutomationController::class);
        Route::post('automations/{automation}/toggle', [\App\Http\Controllers\Api\Mobile\AutomationController::class, 'toggle']);

        // Message Bots
        Route::get('/bots', [\App\Http\Controllers\Api\Mobile\MessageBotController::class, 'index']);
        Route::post('/bots', [\App\Http\Controllers\Api\Mobile\MessageBotController::class, 'store']);
        Route::get('/bots/{bot}', [\App\Http\Controllers\Api\Mobile\MessageBotController::class, 'show']);
        Route::post('/bots/{bot}/duplicate', [\App\Http\Controllers\Api\Mobile\MessageBotController::class, 'duplicate']);
        Route::post('/bots/{bot}/toggle', [\App\Http\Controllers\Api\Mobile\MessageBotController::class, 'toggle']);
        Route::patch('/bots/{bot}', [\App\Http\Controllers\Api\Mobile\MessageBotController::class, 'update']);
        Route::delete('/bots/{bot}', [\App\Http\Controllers\Api\Mobile\MessageBotController::class, 'destroy']);

        // Activity Logs
        Route::get('/activities', [\App\Http\Controllers\Api\Mobile\ActivityController::class, 'index']);
        Route::get('/activities/{activity}', [\App\Http\Controllers\Api\Mobile\ActivityController::class, 'show']);

        // Internal Notes
        Route::get('/conversations/{conversation}/notes', [\App\Http\Controllers\Api\Mobile\ConversationController::class, 'getNotes']);
        Route::post('/conversations/{conversation}/notes', [\App\Http\Controllers\Api\Mobile\ConversationController::class, 'storeNote']);

        // Contacts (single canonical set of routes)
        Route::get('/contacts', [\App\Http\Controllers\Api\Mobile\ContactController::class, 'index']);
        Route::post('/contacts', [\App\Http\Controllers\Api\Mobile\ContactController::class, 'store']);
        Route::get('/contacts/search', [\App\Http\Controllers\Api\Mobile\ContactController::class, 'search']);
        Route::get('/contacts/tags', [\App\Http\Controllers\Api\Mobile\ContactController::class, 'getAvailableTags']);
        Route::post('/contacts/tags', [\App\Http\Controllers\Api\Mobile\ContactController::class, 'storeTag']);
        Route::get('/contacts/{contact}', [\App\Http\Controllers\Api\Mobile\ContactController::class, 'show']);
        Route::get('/contacts/{contact}/activity', [\App\Http\Controllers\Api\Mobile\ContactController::class, 'activity']);
        Route::post('/contacts/{contact}/tags/toggle', [\App\Http\Controllers\Api\Mobile\ContactController::class, 'toggleTag']);
        Route::put('/contacts/{contact}', [\App\Http\Controllers\Api\Mobile\ContactController::class, 'update']);

        // Templates (single canonical set)
        Route::get('/templates', [\App\Http\Controllers\Api\Mobile\TemplateController::class, 'index']);
        Route::post('/templates', [\App\Http\Controllers\Api\Mobile\TemplateController::class, 'store']);
        Route::get('/templates/{template}', [\App\Http\Controllers\Api\Mobile\TemplateController::class, 'show']);
        Route::patch('/templates/{template}', [\App\Http\Controllers\Api\Mobile\TemplateController::class, 'update']);
        Route::post('/templates/{template}/toggle', [\App\Http\Controllers\Api\Mobile\TemplateController::class, 'toggle']);
        Route::post('/templates/{template}/send-test', [\App\Http\Controllers\Api\Mobile\TemplateController::class, 'sendTest']);
        Route::delete('/templates/{template}', [\App\Http\Controllers\Api\Mobile\TemplateController::class, 'destroy']);

        // Media Uploads
        Route::post('/media/upload', [\App\Http\Controllers\Api\Mobile\MediaController::class, 'upload']);
        // Chunked upload: mobile splits large files into ~700 KB chunks to bypass
        // nginx's 1 MB default body-size limit. Server reassembles on final chunk.
        Route::post('/media/upload/chunk', [\App\Http\Controllers\Api\Mobile\ChunkedMediaController::class, 'uploadChunk']);


        // Campaigns / Broadcasting
        Route::get('/campaigns', [\App\Http\Controllers\Api\Mobile\CampaignController::class, 'index']);
        Route::post('/campaigns', [\App\Http\Controllers\Api\Mobile\CampaignController::class, 'store']);
        Route::get('/campaigns/{campaign}', [\App\Http\Controllers\Api\Mobile\CampaignController::class, 'show']);

        // AI Suggest Reply (new)
        Route::post('/conversations/{conversation}/ai-suggest', [\App\Http\Controllers\Api\Mobile\AiController::class, 'suggest']);

        // Team Management
        Route::get('/team/members', function (\Illuminate\Http\Request $request) {
            return response()->json($request->user()->currentTeam->allUsers()->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'initials' => mb_strtoupper(mb_substr($u->name, 0, 2)),
            ]));
        });
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
    ->middleware([\App\Http\Middleware\VerifyWhatsAppSignature::class, 'throttle:600,1']);

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
