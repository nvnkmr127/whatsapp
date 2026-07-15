<?php

namespace Tests\Unit;

use App\Models\Team;
use App\Services\AI\AIProviderManager;
use App\Services\AiCommerceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_chat_routes_to_team_provider_and_applies_team_model()
    {
        $team = Team::factory()->create();
        set_setting("ai_provider_{$team->id}", 'anthropic');
        set_setting("ai_anthropic_api_key_{$team->id}", 'test-key');
        set_setting("ai_model_{$team->id}", 'claude-haiku-4-5');

        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => 'Hello back']],
                'model' => 'claude-haiku-4-5',
                'usage' => ['input_tokens' => 5, 'output_tokens' => 3],
            ]),
        ]);

        $result = AIProviderManager::chat($team, [['role' => 'user', 'content' => 'Hi']]);

        $this->assertTrue($result['success']);
        $this->assertSame('Hello back', $result['content']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.anthropic.com')
                && $request['model'] === 'claude-haiku-4-5'
                && $request->hasHeader('x-api-key', 'test-key');
        });
    }

    public function test_chat_falls_back_to_secondary_provider_on_api_failure()
    {
        $team = Team::factory()->create();
        set_setting("ai_provider_{$team->id}", 'openai');
        set_setting("ai_openai_api_key_{$team->id}", 'bad-key');
        set_setting("ai_fallback_provider_{$team->id}", 'anthropic');
        set_setting("ai_anthropic_api_key_{$team->id}", 'good-key');

        Http::fake([
            'api.openai.com/*' => Http::response(['error' => ['message' => 'invalid key']], 401),
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => 'Rescued']],
                'model' => 'claude-opus-4-8',
                'usage' => [],
            ]),
        ]);

        $result = AIProviderManager::chat($team, [['role' => 'user', 'content' => 'Hi']]);

        $this->assertTrue($result['success']);
        $this->assertSame('Rescued', $result['content']);
    }

    public function test_summarize_sends_prompt_verbatim_and_returns_string()
    {
        $team = Team::factory()->create();
        set_setting("ai_provider_{$team->id}", 'openai');
        set_setting("ai_openai_api_key_{$team->id}", 'test-key');

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'A reply']]],
                'model' => 'gpt-4o',
                'usage' => [],
            ]),
        ]);

        $result = AIProviderManager::summarize($team, 'Suggest a reply to this customer.');

        $this->assertSame('A reply', $result);

        Http::assertSent(function ($request) {
            // Prompt must arrive unmodified — no "Summarize this:" prefix
            return $request['messages'][0]['content'] === 'Suggest a reply to this customer.';
        });
    }

    public function test_extract_json_tolerates_code_fences()
    {
        $plain = AiCommerceService::extractJson('{"matched": false, "reply_text": "Hi"}');
        $this->assertSame('Hi', $plain['reply_text']);

        $fenced = AiCommerceService::extractJson("```json\n{\"matched\": true, \"product_ids\": [1], \"reply_text\": \"Here\"}\n```");
        $this->assertSame('Here', $fenced['reply_text']);
        $this->assertSame([1], $fenced['product_ids']);

        $this->assertNull(AiCommerceService::extractJson('not json at all'));
    }
}
