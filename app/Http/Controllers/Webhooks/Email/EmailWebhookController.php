<?php

namespace App\Http\Controllers\Webhooks\Email;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EmailWebhookController extends Controller
{
    /**
     * Map providers to their respective handlers.
     */
    protected array $handlers = [
        'resend' => ResendWebhookHandler::class,
    ];

    /**
     * Route the webhook to the correct provider handler.
     */
    public function handle(Request $request, string $provider)
    {
        if (! isset($this->handlers[$provider])) {
            throw new NotFoundHttpException("Email webhook handler for provider [{$provider}] not found.");
        }

        // 1. Resolve handler from container
        $handler = app($this->handlers[$provider]);

        // 2. Delegate processing
        $handler->handle($request);

        // 3. Always return success to acknowledge receipt
        return response()->json(['status' => 'ok']);
    }
}
