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

    // Inbound Webhooks (receive from external software)
    Route::post('/webhooks/inbound', [\App\Http\Controllers\Api\InboundWebhookController::class, 'handle']);
    Route::get('/webhooks/inbound/url', [\App\Http\Controllers\Api\InboundWebhookController::class, 'getUrl']);

    // Embed Token (if needed)
    Route::post('/embed-token', [\App\Http\Controllers\EmbedController::class, 'generateToken']);
});

use App\Http\Controllers\WhatsAppWebhookController;

Route::get('/webhook/whatsapp', [WhatsAppWebhookController::class, 'verify'])->name('api.webhook.whatsapp');
Route::post('/webhook/whatsapp', [WhatsAppWebhookController::class, 'handle'])
    ->middleware(\App\Http\Middleware\VerifyWhatsAppSignature::class);
