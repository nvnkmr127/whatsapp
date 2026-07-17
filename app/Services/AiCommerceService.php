<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Contact;
use App\Models\Product;
use App\Services\AI\AIProviderManager;
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

        // 0. The assistant must be switched on — from either settings surface
        // (AI Settings page toggle, or the Commerce dashboard toggle).
        $assistantEnabled = (bool) ($team->ai_auto_reply_enabled ?? false)
            || ! empty(($team->commerce_config ?? [])['ai_assistant_enabled']);
        if (! $assistantEnabled) {
            return false;
        }

        // 0.5 Entitlements — answering a customer needs the 'ai' feature and message
        // budget. Commerce (cart/checkout) is a separate capability, checked below.
        $e = app(EntitlementService::class)->for($team);

        if (! $e->hasFeature('ai')) {
            Log::info("AiCommerceService: AI Bot blocked for team {$team->name} [{$e->statusLabel()}] - feature 'ai' not included.");
            $this->logAiActivity($contact, 'ai_blocked', 'AI Bot blocked: feature "ai" not included in plan');

            return false;
        }

        if (! $e->can('send_message')) {
            Log::info("AiCommerceService: AI Bot blocked for team {$team->name} [{$e->statusLabel()}] - message limit reached.");

            return false;
        }

        // Selling actions (product cards, cart, checkout) need the commerce feature AND
        // a ready store. When unavailable the bot still answers — it just doesn't sell.
        $commerceEnabled = $e->hasFeature('commerce')
            && app(CommerceReadinessService::class)->canPerformAction($team, 'ai_shop');

        $persona = get_setting("ai_persona_{$teamId}")
            ?? 'You are the friendly AI assistant for this business. Help customers with anything they ask.';

        // 1. Tenant knowledge base — the business's own info about its services,
        // products, policies, hours, etc. Consulted by default so the bot answers
        // from the actual business model, not guesses.
        $kbContext = '';
        $kbStrict = false;
        if (get_setting("ai_use_kb_{$teamId}", true)) {
            $kbService = app(KnowledgeBaseService::class);
            $kbScope = get_setting("ai_kb_scope_{$teamId}", 'all');
            $sourceIds = ($kbScope === 'selected')
                ? json_decode(get_setting("ai_kb_source_ids_{$teamId}", '[]'), true)
                : null;

            if ($kbService->isReady($teamId, $sourceIds)) {
                $kbContext = $kbService->searchContext($teamId, $message, $sourceIds);
                $kbStrict = (bool) get_setting("ai_kb_strict_{$teamId}", true);
            }
        }

        // 2. Product catalog (optional — service businesses may have none).
        // Apply the shoppable/readiness scope with the known team — Product::shoppable()
        // resolves the team from $this and is null in a queued (no-auth) context.
        $config = $team->commerce_config ?? [];
        $storeCurrency = ($config['currency'] ?? null) ?: 'USD';

        $products = app(CommerceReadinessService::class)
            ->applyShoppableScope(Product::where('team_id', $teamId), $team)
            ->with('category:id,name')
            ->take(30)
            ->get(['id', 'category_id', 'name', 'price', 'currency', 'description', 'image_url', 'availability', 'stock_quantity', 'manage_stock']);

        // Give the model everything it needs to answer cost/currency/variation/stock
        // questions accurately, instead of just name+price.
        $catalogJson = $products->map(function ($p) use ($storeCurrency) {
            $item = [
                'id' => $p->id,
                'name' => $p->name,
                'price' => $p->price,
                'currency' => $p->currency ?: $storeCurrency,
                'availability' => $p->availability ?: 'in stock',
                'desc' => substr($p->description ?? '', 0, 120),
            ];
            if ($p->category) {
                $item['category'] = $p->category->name;
            }
            if ($p->manage_stock) {
                $item['stock'] = (int) $p->stock_quantity;
            }

            return $item;
        })->toJson();

        // Store-level commerce settings the customer might ask about ("custom settings":
        // currency, cash on delivery, minimum order, guest checkout).
        $storeInfo = array_filter([
            'currency' => $storeCurrency,
            'cash_on_delivery' => ! empty($config['cod_enabled']),
            'guest_checkout' => ! empty($config['allow_guest_checkout']),
            'min_order_value' => $config['min_order_value'] ?? null,
        ], fn ($v) => $v !== null);
        $storeInfoJson = json_encode($storeInfo);

        // 3. Build the system prompt — a general business assistant, not a pure
        // product matcher. Selling behavior depends on whether commerce is available.
        $sellingRule = ($commerceEnabled && $products->isNotEmpty())
            ? 'If the customer wants to buy and specific CATALOG products fit, set "matched": true and list up to 3 relevant "product_ids". Otherwise set "matched": false with an empty list.'
            : 'Set "matched": false and "product_ids": []. This business is not selling products in chat right now, so never offer a cart or checkout — just answer helpfully.';

        $groundingRule = ($kbStrict && $kbContext)
            ? 'For factual questions about this business (services, pricing, policies, hours), answer ONLY from KNOWLEDGE_BASE. If the specific answer is not there, set "kb_gap": true, say politely that you will have the team follow up, and do not invent details. Cite sources as [Source: Name] where relevant.'
            : 'Use the persona, KNOWLEDGE_BASE, and CATALOG to answer. If you are unsure of a specific fact, be honest and offer to connect the customer with the team.';

        $systemPrompt = <<<PROMPT
{$persona}

You are a customer-facing WhatsApp assistant. Answer ANY question a customer asks — about services, products, pricing, availability, policies, business hours, or general queries — as helpfully as you can using the context below. Be warm and concise.

RULES:
1. {$groundingRule}
2. {$sellingRule}
3. PRICING: When you quote a price, always state it with its currency (each CATALOG item carries its own "price" and "currency"). Mention colour/variation, category, and stock/availability when the customer asks or when it helps them decide. Never invent a price, variation, or currency that is not in the CATALOG.
4. STORE: Use STORE_INFO to answer questions about payment (e.g. cash on delivery), minimum order value, guest checkout, and the store's default currency.
5. Respond with ONLY a JSON object: {"matched": <true|false>, "product_ids": [<ids>], "reply_text": "<your reply>", "kb_gap": <true|false>}.
6. Never leave "reply_text" empty — always give the customer a useful answer.
7. LANGUAGE: Detect the language of the customer's message and write "reply_text" in that SAME language (e.g. reply in Hindi to a Hindi message). Voice notes are transcribed to text before you see them, so treat them like any typed message. Keep product names as written in the CATALOG.

STORE_INFO:
{$storeInfoJson}

KNOWLEDGE_BASE:
{$kbContext}

CATALOG:
{$catalogJson}
PROMPT;

        // 3. Call the team's configured AI provider (with fallback chain)
        try {
            $response = AIProviderManager::chat($team, [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $message],
            ], [
                'temperature' => 0.7,
                'response_format' => ['type' => 'json_object'],
            ]);

            $aiData = $response['content'] ?? '';
            Log::debug('AI Response Content: '.$aiData);
            $aiJson = self::extractJson($aiData);

            if (! isset($aiJson['reply_text'])) {
                return false;
            }

            // Record Usage for Billing
            $this->logAiActivity($contact, 'ai_sent', 'AI Response sent: '.substr($aiJson['reply_text'], 0, 50).'...');

            // Log a knowledge gap when the bot couldn't answer a business question from the KB
            if (! empty($aiJson['kb_gap'])) {
                app(KnowledgeBaseService::class)->logGap($teamId, $message, 'unanswered');
            }

            // 4. Send Response
            $this->whatsappService->setTeam($team);

            // A. Send Text Response first
            $this->whatsappService->sendText($contact->phone_number, $aiJson['reply_text']);

            // B. If products matched and the store can sell, initialize cart and provide link
            if ($commerceEnabled && ! empty($aiJson['matched']) && ! empty($aiJson['product_ids'])) {
                $this->sendProductCards($contact, $products, $aiJson['product_ids']);

                // Technical: Pre-fill cart for the user if they were specifically looking for these
                // This makes the 'Checkout' button actually useful immediately.
                $this->prefillCart($contact, $aiJson['product_ids']);

                // C. Send Checkout Link
                $cart = app(CartService::class)->getOrCreateCart($contact, $team);
                $checkoutUrl = route('commerce.checkout', ['uuid' => $cart->uuid]);

                $this->whatsappService->sendText(
                    $contact->phone_number,
                    "🛒 *Checkout Link:* {$checkoutUrl}\n\nI've prepared a cart with these items for you. Click above to complete your order!"
                );
            }

            return true;

        } catch (\Exception $e) {
            Log::error('AiCommerceService Error: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Parse a JSON object from model output, tolerating markdown code fences
     * (Anthropic/Gemini may wrap JSON even when asked not to).
     */
    public static function extractJson(string $raw): ?array
    {
        $decoded = json_decode(trim($raw), true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $raw, $m)) {
            $decoded = json_decode($m[0], true);
        }

        return is_array($decoded) ? $decoded : null;
    }

    protected function sendProductCards(Contact $contact, $allProducts, $selectedIds)
    {
        // Filter the full collection to get details of selected IDs
        $matches = $allProducts->whereIn('id', $selectedIds);

        $storeCurrency = ($contact->team->commerce_config['currency'] ?? null) ?: 'USD';

        foreach ($matches as $product) {
            // Send each product as a Media Message (Image) with Caption containing Name, Price
            // In a better version, we'd use WhatsApp "Catalog" messages or Multi-Product messages (complex setup).
            // MVP: Image + Caption + "Add to Cart" Button (Text driven? No, use Interactive Button if possible)

            // Image + Caption — show price with currency, and colour/variation if set
            $currency = $product->currency ?: $storeCurrency;
            $variation = '';
            $caption = "*{$product->name}*\nPrice: {$currency} {$product->price}{$variation}\n\n{$product->description}";

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

    protected function prefillCart(Contact $contact, array $productIds)
    {
        try {
            $cartService = app(CartService::class);
            $cart = $cartService->getOrCreateCart($contact, $contact->team);

            foreach ($productIds as $id) {
                $product = Product::find($id);
                if ($product && $product->team_id === $contact->team_id) {
                    $cartService->addItem($cart, $product, 1);
                }
            }
        } catch (\Exception $e) {
            Log::error('AiCommerceService: Prefill failed: '.$e->getMessage());
        }
    }

    protected function logAiActivity(Contact $contact, string $action, string $description)
    {
        ActivityLog::create([
            'team_id' => $contact->team_id,
            'action' => $action,
            'description' => $description,
            'subject_type' => get_class($contact),
            'subject_id' => $contact->id,
            'properties' => ['is_ai' => true],
        ]);
    }
}
