<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Product;
use App\Models\Team;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiCommerceService
{
    protected $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Handle incoming user message if AI Assistant is enabled.
     * Returns true if handled (response sent), false otherwise.
     */
    public function handle(Contact $contact, string $message): bool
    {
        $team = $contact->team;
        $teamId = $team->id;

        // 0. Commerce Readiness Check
        $readinessService = app(\App\Services\CommerceReadinessService::class);
        if (!$readinessService->canPerformAction($team, 'ai_shop')) {
            Log::info("AiCommerceService: AI Bot blocked for team {$team->name} due to readiness failure.");
            return false;
        }

        // Fetch Centralized Settings
        $apiKey = \App\Models\Setting::where('key', "ai_openai_api_key_{$teamId}")->value('value') ?? env('OPENAI_API_KEY');
        $model = \App\Models\Setting::where('key', "ai_openai_model_{$teamId}")->value('value') ?? 'gpt-4o';
        $persona = \App\Models\Setting::where('key', "ai_persona_{$teamId}")->value('value') ?? "You are a helpful shopping assistant for a store. Your goal is to help the user find products from the CATALOG provided below.";

        if (!$apiKey) {
            Log::warning("AI Assistant enabled for team {$team->name} but OPENAI_API_KEY is missing (checked Settings and ENV).");
            return false;
        }

        Log::debug("AiCommerceService: Found API key, model={$model}.");

        // 1. Fetch Product Catalog Summary
        $products = Product::shoppable()
            ->where('team_id', $teamId)
            ->take(30)
            ->get(['id', 'name', 'price', 'description', 'image_url']);

        if ($products->isEmpty()) {
            return false;
        }

        $catalogJson = $products->map(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'price' => $p->price,
                'desc' => substr($p->description ?? '', 0, 100)
            ];
        })->toJson();

        // 2. Prepare System Prompt
        $systemPrompt = "{$persona}
        
        RULES:
        1. Access the user's need.
        2. Select up to 3 matching products from the CATALOG.
        3. If products match, return ONLY a JSON object with this format:
           {\"matched\": true, \"product_ids\": [1, 2], \"reply_text\": \"Here are some options...\"}
        4. If NO products match, but the user is chatting socially, answer politely but steer back to shopping. Return JSON:
           {\"matched\": false, \"reply_text\": \"Your polite response...\"}
        5. Keep `reply_text` short and friendly.

        CATALOG:
        {$catalogJson}
        ";

        // 3. Call OpenAI
        try {
            $response = Http::withToken($apiKey)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $message],
                ],
                'temperature' => 0.7,
                'response_format' => ['type' => 'json_object']
            ]);

            if ($response->failed()) {
                Log::error("OpenAI API Failed: " . $response->body());
                return false;
            }

            $aiData = $response->json('choices.0.message.content');
            Log::debug("OpenAI Response Content: " . $aiData);
            $aiJson = json_decode($aiData, true);

            if (!isset($aiJson['reply_text'])) {
                return false;
            }

            // 4. Send Response
            $this->whatsappService->setTeam($team);

            // A. Send Text Response first
            $this->whatsappService->sendText($contact->phone_number, $aiJson['reply_text']);

            // B. If products matched, send them as Interactive List or Carousel
            if (!empty($aiJson['matched']) && !empty($aiJson['product_ids'])) {
                $this->sendProductCards($contact, $products, $aiJson['product_ids']);
            }

            return true;

        } catch (\Exception $e) {
            Log::error("AiCommerceService Error: " . $e->getMessage());
            return false;
        }
    }

    protected function sendProductCards(Contact $contact, $allProducts, $selectedIds)
    {
        // Filter the full collection to get details of selected IDs
        $matches = $allProducts->whereIn('id', $selectedIds);

        foreach ($matches as $product) {
            // Send each product as a Media Message (Image) with Caption containing Name, Price
            // In a better version, we'd use WhatsApp "Catalog" messages or Multi-Product messages (complex setup).
            // MVP: Image + Caption + "Add to Cart" Button (Text driven? No, use Interactive Button if possible)

            // Image + Caption
            $caption = "*{$product->name}*\nPrice: {$product->price}\n\n{$product->description}";

            if ($product->image_url) {
                // Send Image
                // Note: Interactive messages with Header Image are "Product Messages" which require Catalog ID.
                // We will stick to simple Media Message for MVP.
                $this->whatsappService->sendMedia(
                    $contact->phone_number,
                    'image',
                    $product->image_url,
                    $caption
                );
            } else {
                $this->whatsappService->sendText($contact->phone_number, $caption);
            }

            // Send "Add to Cart" button (Separate message due to API limits on mixing media+buttons easily without templates)
            // Actually, we can just ask user to reply.
            // "Reply 'ADD {$product->id}' to buy."
        }

        $this->whatsappService->sendText($contact->phone_number, "To buy, simply reply with the product name or 'Add <Name>'!");
    }
}
