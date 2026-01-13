<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/webhooks/trigger/{id}', [\App\Http\Controllers\Api\WebhookTriggerController::class, 'trigger'])->middleware('auth:sanctum');

Route::group(['middleware' => ['auth:sanctum', 'throttle:api']], function () {
    Route::post('/v1/contacts', [\App\Http\Controllers\ExternalContactController::class, 'store']);
    Route::post('/v1/embed-token', [\App\Http\Controllers\EmbedController::class, 'generateToken']);

    // Conversation API
    Route::get('/v1/conversations/{phone}', [\App\Http\Controllers\ExternalConversationController::class, 'index']);
    Route::post('/v1/messages', [\App\Http\Controllers\ExternalConversationController::class, 'send']);
});

use App\Http\Controllers\WhatsAppWebhookController;

Route::get('/webhook/whatsapp', [WhatsAppWebhookController::class, 'verify'])->name('whatsapp.webhook');
Route::post('/webhook/whatsapp', [WhatsAppWebhookController::class, 'handle'])
    ->middleware(\App\Http\Middleware\VerifyWhatsAppSignature::class);
