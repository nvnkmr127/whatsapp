# AI Assistant Configuration (AI Settings)

## 1. What is this page?
The AI Assistant Configuration (AI Settings) page is the artificial intelligence control deck of the platform. Located at `/settings/ai`, it allows administrators to manage LLM API connections (OpenAI, Anthropic, Gemini, DeepSeek), define prompt personas, configure auto-reply switches, select backup models, adjust creativity parameters, set safety stop words, and link knowledge bases.

## 2. Why is this page useful?
Deploying an AI customer agent requires robust API settings, safe fallback mechanisms, and grounding context to prevent hallucinations.
- **Why do users need it?** To set API tokens, configure safe fallback models for system uptime, set guardrails (like stop words), and link specific files from their Business Brain to the AI chat loop.
- **What work does it make easier?** It offers predefined role presets (Support, Sales, Commerce), tests API connections before deploying, and wraps AI outputs in standard header/footer messages automatically.
- **What business process does it support?** AI Agent Behavior Definition, Multi-LLM API Integration, and Grounded Question Answering.
- **What happens without it?** The AI assistant cannot authenticate with providers, has no persona guidelines, and cannot connect to knowledge sources.

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Admin | To authorize API tokens, connect database settings, manage provider integrations, and set up safety fallbacks. |
| Customer Support Lead | To test prompt responses, adjust creativity ranges (temperature), and restrict knowledge retrieval boundaries. |

## 4. What can users do here?
- **Manage Auto-Reply Terminal:** Toggle the global active status of the AI auto-reply system (`ai_auto_reply_enabled`).
- **Define AI Behavior:**
  - Select role presets (Help Desk Agent, Sales Assistant, Commerce Guide, Custom Instructions).
  - Write custom persona instructions in the AI Persona textarea.
- **Configure AI Provider Connections:**
  - Select LLM providers (OpenAI, Anthropic, Google Gemini, DeepSeek).
  - Input provider-specific API credentials.
  - Select primary and fallback models.
  - **Safe Mode Fallback:** Select a secondary LLM provider and model to handle requests if primary services suffer outages.
  - Test Connection: Direct API check button to verify key validity.
- **Set Model Parameters:**
  - Adjust Temperature (creativity slider ranging from 0.0 to 1.0).
  - Set Retry Attempts (tries again if a query fails).
- **Manage Message Formatting:**
  - Toggle and write **Header Messages** (prefix appended to the start of every reply).
  - Toggle and write **Footer Messages** (suffix appended to the end of every reply).
- **Configure Safety Guardrails:**
  - Toggle and input **Stop Words** (phrases like "STOP" or "CANCEL" that halt AI responses).
  - Set a **Fallback Message** (displayed if the AI fails after all retries).
- **Connect Business Brain (Knowledge Retrieval):**
  - Toggle Business Brain grounding.
  - Set search scopes: "All Sources" (checks everything) or "Selected Only" (checks checked files only).
  - Toggle **Strict Grounding:** Restricts the AI to only answer questions using the knowledge base, preventing hallucinations.

## 5. What is involved?
- **AIProviderManager:** Connects to OpenAI, Anthropic, Gemini, and DeepSeek SDKs.
- **Settings System:** Saves API keys and prompt configurations in the team's settings database.
- **Team Model:** Manages the `ai_auto_reply_enabled` toggle status.

## 6. How does it work?
1. The Admin goes to `/settings/ai` and selects "OpenAI".
2. They input their OpenAI API key and click "Test Connection" to confirm it is valid.
3. They select `gpt-4o` as their primary model and `gemini-1.5-pro` as their fallback provider.
4. They toggle on "AI Auto-Reply Terminal" to activate bot support.
5. They toggle on "Business Brain" and select "Strict Grounding" to ensure the bot only answers using uploaded PDFs.
6. Under Footer Message, they type: `_🤖 AI Assistant. Type "agent" to connect to a human._`
7. They click "Save Settings". The system saves the values in the team settings, activating the AI bot for incoming WhatsApp messages.

## 7. What happens behind the scenes?
- **Multi-LLM Integration Pipeline:** The `AIProviderManager` maps incoming user messages, merges them with persona prompts, appends the header/footer strings, and directs the query to the primary model's endpoints.
- **Automatic Safe-Mode Failover:** If the primary LLM API returns a 500 error or rate-limit code, the system retries up to the configured limit. If errors persist, it redirects the payload to the fallback provider. If both fail, it sends the configured fallback text.
- **Knowledge Base Scope Injection:** If the Business Brain is enabled, the prompt engine queries the vector index before calling the LLM, pulling matches from the selected files, and formatting them as context wrappers. If Strict Grounding is active, a prompt is appended instructing the LLM to reply "I do not know" if context matches are empty.

## 8. Business Use Cases

**Use Case 1: Enabling Dynamic E-Commerce Assistant**
- **Situation:** A storefront wants their AI assistant to suggest catalog items and guide checkout inquiries.
- **How the feature is used:** They select the "Commerce Assistant" preset, toggle on Business Brain integration with "All Sources", and connect catalog files.
- **Customer experience:** Customers asking about product features receive recommendations matching catalog specs.
- **Business outcome:** High-volume customer queries resolved without manual support.

**Use Case 2: Configuring Safe Failover System**
- **Situation:** A business wants to ensure support stays active even if OpenAI suffers an outage.
- **How the feature is used:** They set OpenAI as primary and Anthropic as fallback.
- **Customer experience:** During an OpenAI outage, customers receive responses from Claude without noticing any change.
- **Business outcome:** Continuous AI uptime.

**Use Case 3: Setting Up Human Handover Triggers**
- **Situation:** A brand wants to ensure customers can exit the AI bot and speak to an agent at any time.
- **How the feature is used:** They set "agent, human, support" as Stop Words and add a footer: "Type 'agent' to speak with a human."
- **Customer experience:** A customer types "agent". The AI immediately stops, and the thread is routed to the human team in the chat dashboard.
- **Business outcome:** Safe chatbot exits.

## 9. Industry Use Cases
- **Retail:** Using commerce presets to suggest items.
- **Education:** Setting tutor presets with low temperature (0.2) to guide students.
- **Real Estate:** Using high temperature (0.9) sales personas to write engaging pitches.

## 10. Real Customer Example
A delivery service connects their Gemini key. They select "Help Desk Agent" preset and set the temperature to 0.5. They toggle on the auto-reply terminal, add a footer message indicating it is an AI, and set "STOP" as a stop keyword. They link their shipping policy document. If Gemini experiences latency, the system automatically redirects the query to Claude. When customers type "STOP", the bot turns off, routing the conversation to the team inbox.

## 11. Customer Journey
Select provider &rarr; Input API key &rarr; Test connectivity &rarr; Select models & temperature &rarr; Write persona rules &rarr; Map headers/footers/stop words &rarr; Ground AI in knowledge documents &rarr; Toggle auto-replies active.

## 12. Inputs
- Provider choices (OpenAI, Anthropic, Gemini, DeepSeek).
- API credentials.
- Primary and fallback models.
- Instructions and role presets.
- Suffixes/prefixes, temperature values, retry parameters, and stop words.
- Checked knowledge documents.

## 13. Outputs
- Encrypted configuration settings.
- Direct connection checks.
- Team auto-reply updates.

## 14. Dependencies
- **Settings System:** Saves credentials.
- **Team Model:** Global terminal toggle.
- **AIProviderManager:** API handler.

## 15. Permissions
- **Who can access this page:** Users with `manage-settings` permission on plans including `ai`.
- **Who can view information:** Admins/Managers.
- **Who can edit:** Admins.
- **Who cannot access it:** Standard support agents.

## 16. Important Rules
- You cannot activate the AI assistant without adding an active API key.
- Strict grounding requires at least one verified document in the Business Brain.

## 17. Common Problems
- **Problem:** "Connection Failed" error during test.
  - **Possible reason:** The API key is invalid, has expired, or is missing billing funds.
  - **What the user should do:** Log in to your provider dashboard, verify your billing credits, generate a new API key, paste it here, and retest.
- **Problem:** AI is ignoring the uploaded Business Brain files.
  - **Possible reason:** The Business Brain toggle is off, or the selected document status is not "Ready".
  - **What the user should do:** Confirm "Enable for Global Assistant" is checked, set scope to "All Sources", and check the files dashboard to confirm processing has finished.

## 18. Simple Explanation for Sales
The AI Settings page is the control panel for your AI bot. Here, you connect your API keys, decide how friendly or professional your bot should be, set up backup systems in case of outages, and decide which FAQs the bot should read.

## 19. Simple Explanation for Marketing
Customize your AI's voice. Choose from presets like sales or support, link knowledge bases, and configure headers/footers to match your brand's style.

## 20. Simple Explanation for Support
If the AI bot is acting outside its boundaries, admins can visit this page to adjust prompts, reduce creativity, or add stop words so agents can take over conversations instantly.

## 21. Related Features
- [Business Brain Manager](./knowledge-base.md)
- [Fulfillment Settings](./commerce-settings.md)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/settings/ai`
- **Implementation:** `App\Livewire\Settings\AiSettings`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Livewire/Settings/AiSettings.php`
  - `resources/views/livewire/settings/ai-settings.blade.php`
  - `app/Services/AI/AIProviderManager.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
