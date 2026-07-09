<?php

namespace App\Services\Mcp;

use App\Services\Mcp\Tools\AddInternalNoteTool;
use App\Services\Mcp\Tools\AssignConversationTool;
use App\Services\Mcp\Tools\CloseConversationTool;
use App\Services\Mcp\Tools\EndCallTool;
use App\Services\Mcp\Tools\GetAiSuggestReplyTool;
use App\Services\Mcp\Tools\GetAnalyticsTool;
use App\Services\Mcp\Tools\GetBusinessProfileTool;
use App\Services\Mcp\Tools\GetCallHistoryTool;
use App\Services\Mcp\Tools\GetCannedMessagesTool;
use App\Services\Mcp\Tools\GetContactActivityTool;
use App\Services\Mcp\Tools\GetContactMessagesTool;
use App\Services\Mcp\Tools\GetContactTagsTool;
use App\Services\Mcp\Tools\GetContactTool;
use App\Services\Mcp\Tools\GetConversationMessagesTool;
use App\Services\Mcp\Tools\GetDashboardAnalyticsTool;
use App\Services\Mcp\Tools\GetTemplatesTool;
use App\Services\Mcp\Tools\GetTeamMembersTool;
use App\Services\Mcp\Tools\InitiateCallTool;
use App\Services\Mcp\Tools\ListAutomationsTool;
use App\Services\Mcp\Tools\ListCampaignsTool;
use App\Services\Mcp\Tools\ListConversationsTool;
use App\Services\Mcp\Tools\MarkMessageReadTool;
use App\Services\Mcp\Tools\ReopenConversationTool;
use App\Services\Mcp\Tools\SearchContactsTool;
use App\Services\Mcp\Tools\SendCarouselTool;
use App\Services\Mcp\Tools\SendContactCardTool;
use App\Services\Mcp\Tools\SendFlowTool;
use App\Services\Mcp\Tools\SendInteractiveButtonsTool;
use App\Services\Mcp\Tools\SendInteractiveListTool;
use App\Services\Mcp\Tools\SendLocationRequestTool;
use App\Services\Mcp\Tools\SendMediaTool;
use App\Services\Mcp\Tools\SendMessageTool;
use App\Services\Mcp\Tools\SendTemplateTool;
use App\Services\Mcp\Tools\StartAutomationTool;
use App\Services\Mcp\Tools\TagContactTool;
use App\Services\Mcp\Tools\ToggleAiTool;
use App\Services\Mcp\Tools\TriggerWebhookTool;
use App\Services\Mcp\Tools\UpsertContactTool;

class McpToolRegistry
{
    /** @var array<string, class-string<McpTool>> */
    private array $tools = [
        // ── Messaging ──────────────────────────────────────────────────────
        'send_message'              => SendMessageTool::class,
        'send_template'             => SendTemplateTool::class,
        'send_media'                => SendMediaTool::class,
        'send_interactive_buttons'  => SendInteractiveButtonsTool::class,
        'send_interactive_list'     => SendInteractiveListTool::class,
        'send_carousel'             => SendCarouselTool::class,
        'send_flow'                 => SendFlowTool::class,
        'send_contact_card'         => SendContactCardTool::class,
        'send_location_request'     => SendLocationRequestTool::class,
        'mark_message_read'         => MarkMessageReadTool::class,

        // ── Contacts ───────────────────────────────────────────────────────
        'get_contact'               => GetContactTool::class,
        'get_contact_messages'      => GetContactMessagesTool::class,
        'get_contact_activity'      => GetContactActivityTool::class,
        'search_contacts'           => SearchContactsTool::class,
        'upsert_contact'            => UpsertContactTool::class,
        'get_contact_tags'          => GetContactTagsTool::class,
        'tag_contact'               => TagContactTool::class,

        // ── Conversations ──────────────────────────────────────────────────
        'list_conversations'        => ListConversationsTool::class,
        'get_conversation_messages' => GetConversationMessagesTool::class,
        'close_conversation'        => CloseConversationTool::class,
        'reopen_conversation'       => ReopenConversationTool::class,
        'assign_conversation'       => AssignConversationTool::class,
        'add_internal_note'         => AddInternalNoteTool::class,
        'toggle_ai'                 => ToggleAiTool::class,

        // ── Automations & Campaigns ────────────────────────────────────────
        'list_automations'          => ListAutomationsTool::class,
        'start_automation'          => StartAutomationTool::class,
        'list_campaigns'            => ListCampaignsTool::class,

        // ── Templates & Quick Replies ──────────────────────────────────────
        'get_templates'             => GetTemplatesTool::class,
        'get_canned_messages'       => GetCannedMessagesTool::class,

        // ── AI ─────────────────────────────────────────────────────────────
        'get_ai_suggest_reply'      => GetAiSuggestReplyTool::class,

        // ── Calls ──────────────────────────────────────────────────────────
        'initiate_call'             => InitiateCallTool::class,
        'end_call'                  => EndCallTool::class,
        'get_call_history'          => GetCallHistoryTool::class,

        // ── Team & Account ─────────────────────────────────────────────────
        'get_team_members'          => GetTeamMembersTool::class,
        'get_business_profile'      => GetBusinessProfileTool::class,
        'get_dashboard_analytics'   => GetDashboardAnalyticsTool::class,
        'get_analytics'             => GetAnalyticsTool::class,

        // ── Webhooks ──────────────────────────────────────────────────────
        'trigger_webhook'           => TriggerWebhookTool::class,
    ];

    public function manifest(): array
    {
        return array_values(array_map(
            fn($class) => app($class)->schema(),
            $this->tools
        ));
    }

    public function resolve(string $name): McpTool
    {
        if (! isset($this->tools[$name])) {
            throw new \InvalidArgumentException("Unknown tool: {$name}");
        }

        return app($this->tools[$name]);
    }
}
