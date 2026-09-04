# WhatsApp Business Platform - API Documentation Reference

Comprehensive developer specification for the WhatsApp Business API suite, including External REST endpoints, Calling VoIP APIs, AI Agent Model Context Protocol (MCP), Multi-Agent Concurrency, E-Commerce sync, Mobile application APIs, and Webhook subscriptions.

---

## Table of Contents

1. [Overview & Standards](#1-overview--standards)
2. [Authentication & Workspace Scoping](#2-authentication--workspace-scoping)
3. [Headers, Idempotency & Rate Limits](#3-headers-idempotency--rate-limits)
4. [Standard Request & Response Envelope](#4-standard-request--response-envelope)
5. [External Public Messaging API](#5-external-public-messaging-api)
   - [Contacts (`/api/v1/contacts`)](#51-contacts-apiv1contacts)
   - [Messages (`/api/v1/messages`)](#52-messages-apiv1messages)
   - [Conversations History (`/api/v1/conversations/{phone}`)](#53-conversations-history-apiv1conversationsphone)
   - [Message Templates (`/api/v1/templates`)](#54-message-templates-apiv1templates)
   - [OTP Verification (`/api/v1/otp`)](#55-otp-verification-apiv1otp)
6. [WhatsApp Calling & WebRTC VoIP API](#6-whatsapp-calling--webrtc-voip-api)
   - [Call Operations (`/api/v1/calls`)](#61-call-operations-apiv1calls)
   - [Call Settings & Permissions (`/api/v1/whatsapp`)](#62-call-settings--permissions-apiv1whatsapp)
7. [Model Context Protocol (MCP) for AI Agents](#7-model-context-protocol-mcp-for-ai-agents)
8. [Embedded Chat Widget Tokens](#8-embedded-chat-widget-tokens)
9. [Multi-Agent Conversation Concurrency & Locks](#9-multi-agent-conversation-concurrency--locks)
10. [Inbox Contact Resolution & Concurrency](#10-inbox-contact-resolution--concurrency)
11. [E-Commerce Integrations & Catalog](#11-e-commerce-integrations--catalog)
12. [Mobile Application Client API](#12-mobile-application-client-api)
13. [Webhooks & Real-Time Event Subscriptions](#13-webhooks--real-time-event-subscriptions)

---

## 1. Overview & Standards

- **Base URL:** `https://your-domain.com/api`
- **Protocol:** HTTPS (TLS 1.2+ mandatory)
- **Data Format:** `application/json` (UTF-8 encoded)
- **Time Format:** ISO 8601 UTC (`YYYY-MM-DDTHH:MM:SSZ`)
- **Phone Number Format:** E.164 recommendation (e.g. `+1234567890`), flexible with leading digits.

---

## 2. Authentication & Workspace Scoping

All endpoints under `/api/v1/*` (except public/source webhooks and auth login) enforce authentication via **Laravel Sanctum Bearer Tokens**.

```http
Authorization: Bearer <YOUR_API_TOKEN>
```

### Workspace / Tenant Resolution (`X-Tenant-ID`)

The platform enforces multi-tenant isolation. When an API token belongs to a user who is a member of multiple workspaces/teams:
- Pass the `X-Tenant-ID` header with the target team ID:
```http
X-Tenant-ID: 15
```
- If omitted, the request scopes to the user's `current_team_id`.
- If the token holder does not belong to the requested tenant, HTTP `403 Forbidden` is returned.

---

## 3. Headers, Idempotency & Rate Limits

### Mandatory & Optional Headers

| Header | Description | Example |
|---|---|---|
| `Authorization` | Bearer API token | `Bearer 1|q9...` |
| `Accept` | Desired response format | `application/json` |
| `Content-Type` | Payload format | `application/json` |
| `X-Tenant-ID` | Workspace scope selector | `12` |
| `X-Idempotency-Key` | Unique UUID to safely retry message dispatches without duplicates | `550e8400-e29b-41d4-a716-446655440000` |

### Rate Limiting

- **General API:** 120 requests/minute per token/IP (`throttle:api`).
- **Mobile Auth:** 10 requests/minute (`throttle:mobile-auth`).
- **Inbound Webhooks:** 600 requests/minute (`throttle:600,1`).
- When exceeded, the server responds with HTTP `429 Too Many Requests` including `Retry-After: <seconds>`.

---

## 4. Standard Request & Response Envelope

### Success Response
```json
{
  "success": true,
  "data": { ... },
  "message": "Operation completed successfully"
}
```

### Error Response
```json
{
  "success": false,
  "message": "Validation failed",
  "code": 42201,
  "errors": {
    "phone_number": [
      "The phone number field is required."
    ]
  }
}
```

### HTTP Status Codes

- `200 OK`: Request succeeded.
- `201 Created`: Resource successfully created.
- `202 Accepted`: Asynchronous job queued (e.g., message dispatched).
- `400 Bad Request`: Invalid request parameters or missing headers.
- `401 Unauthorized`: Missing or invalid Bearer token.
- `403 Forbidden`: Token does not have permission or tenant access.
- `404 Not Found`: Target entity not found in active workspace.
- `409 Conflict`: Concurrency version mismatch or active lock held by another agent.
- `422 Unprocessable Content`: Validation failure.
- `429 Too Many Requests`: Rate limit exceeded.
- `500 Internal Server Error`: Server-side unhandled exception.

---

## 5. External Public Messaging API

### 5.1 Contacts (`/api/v1/contacts`)

#### Fetch Contacts (Paginated)
- **Method:** `GET /api/v1/contacts`
- **Query Parameters:**
  - `page` (integer, optional): Page number (defaults to 1, 50 records per page).
  - `query` (string, optional): Search by name or phone number.
  - `tag` (string, optional): Filter by tag slug.
- **cURL:**
```bash
curl -X GET "https://your-domain.com/api/v1/contacts?page=1" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "X-Tenant-ID: 1" \
  -H "Accept: application/json"
```
- **Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 101,
      "phone_number": "+1234567890",
      "name": "Jane Wilson",
      "email": "jane@example.com",
      "opt_in_status": "opted_in",
      "opt_in_at": "2026-08-10T14:22:00Z",
      "custom_attributes": {
        "tier": "enterprise",
        "company": "Acme Corp"
      },
      "created_at": "2026-08-10T14:22:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "total": 1
  }
}
```

#### Create or Update Contact
- **Method:** `POST /api/v1/contacts`
- **Behavior:** Safely merges `custom_attributes` (does not destroy existing keys). Preserves existing email if omitted in update. Automatically logs opt-in consent if `opt_in: true`.
- **Payload:**
```json
{
  "phone_number": "+1234567890",
  "name": "Jane Wilson",
  "email": "jane@example.com",
  "custom_attributes": {
    "company": "Acme Corp",
    "tier": "enterprise"
  },
  "opt_in": true,
  "opt_in_source": "API"
}
```
- **Response (200 OK / 201 Created):**
```json
{
  "success": true,
  "message": "Contact synced successfully",
  "data": {
    "id": 101,
    "phone_number": "+1234567890",
    "name": "Jane Wilson",
    "email": "jane@example.com",
    "custom_attributes": {
      "company": "Acme Corp",
      "tier": "enterprise"
    },
    "opt_in_status": "opted_in"
  }
}
```

---

### 5.2 Messages (`/api/v1/messages`)

- **Method:** `POST /api/v1/messages`
- **Header:** `X-Idempotency-Key` (recommended)
- **Supported Formats:** Accepts Meta-style payload envelopes or flat fields.

#### A. Standard Text Message (Meta Envelope)
```json
{
  "to": "+1234567890",
  "type": "text",
  "text": {
    "body": "Your appointment is confirmed for tomorrow at 10:00 AM."
  }
}
```

#### B. WhatsApp Template Message
```json
{
  "to": "+1234567890",
  "type": "template",
  "template": {
    "name": "order_update",
    "language": {
      "code": "en_US"
    },
    "components": [
      {
        "type": "body",
        "parameters": [
          { "type": "text", "text": "Order #9821" },
          { "type": "text", "text": "Shipped" }
        ]
      }
    ]
  }
}
```

#### C. Flat Payload Format
```json
{
  "phone_number": "+1234567890",
  "message": "Hello from external webhook!",
  "template_name": "welcome_notice",
  "language": "en_US",
  "template_params": ["Customer Name"]
}
```

- **Response (202 Accepted):**
```json
{
  "success": true,
  "message": "Message accepted and queued for delivery",
  "status": "queued",
  "message_id": 4819,
  "conversation_id": 312
}
```

---

### 5.3 Conversations History (`/api/v1/conversations/{phone}`)

- **Method:** `GET /api/v1/conversations/{phone}`
- **Description:** Returns the 50 most recent messages exchanged with the contact in the latest active conversation thread.
- **Path Parameter:** `phone` (with or without leading `+`).
- **Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "conversation_id": 312,
    "contact": {
      "id": 101,
      "name": "Jane Wilson",
      "phone_number": "+1234567890"
    },
    "status": "active",
    "messages": [
      {
        "id": 4818,
        "direction": "inbound",
        "message": "When will my order ship?",
        "type": "text",
        "status": "read",
        "created_at": "2026-08-10T14:20:00Z"
      },
      {
        "id": 4819,
        "direction": "outbound",
        "message": "Your order #9821 has been shipped!",
        "type": "template",
        "status": "delivered",
        "created_at": "2026-08-10T14:21:00Z"
      }
    ]
  }
}
```

---

### 5.4 Message Templates (`/api/v1/templates`)

#### List Templates
- **Method:** `GET /api/v1/templates`
- **Query Parameters:**
  - `force_refresh` (boolean, optional): Set to `1` to bypass local database cache and sync live with Meta Cloud API.
  - `status` (string, optional): Filter by `APPROVED`, `PENDING`, `REJECTED`.
- **Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 12,
      "name": "order_update",
      "language": "en_US",
      "category": "UTILITY",
      "status": "APPROVED",
      "components": [
        {
          "type": "BODY",
          "text": "Your order {{1}} status is now {{2}}."
        }
      ]
    }
  ]
}
```

#### Show Template
- **Method:** `GET /api/v1/templates/{id}`
- **Description:** Retrieve template by database ID, Meta template ID, or template name.

---

### 5.5 OTP Verification (`/api/v1/otp`)

- **Method:** `POST /api/v1/otp/verify`
- **Security:** Tenant-isolated, 5-minute TTL, max 5 verification attempts.
- **Payload:**
```json
{
  "phone": "+1234567890",
  "code": "543210"
}
```
- **Response (200 OK):**
```json
{
  "success": true,
  "message": "Phone number verified successfully"
}
```
- **Error Responses:**
  - `422 Unprocessable Content`: `{"success": false, "message": "Invalid or expired verification code"}`
  - `429 Too Many Requests`: `{"success": false, "message": "Maximum verification attempts exceeded"}`

---

## 6. WhatsApp Calling & WebRTC VoIP API

### 6.1 Call Operations (`/api/v1/calls`)

#### Preflight Eligibility Check
- **Method:** `POST /api/v1/calls/check-eligibility`
- **Description:** Checks whether contact is within the customer 24-hour consent window, calling is enabled on the subscription plan, and monthly calling minutes are available.
- **Payload:**
```json
{
  "contact_id": 101
}
```
- **Response (200 OK):**
```json
{
  "success": true,
  "eligible": true,
  "reason": null,
  "remaining_minutes": 240
}
```

#### Initiate Call
- **Method:** `POST /api/v1/calls/initiate`
- **Payload:**
```json
{
  "phone_number": "+1234567890",
  "sdp": "v=0\r\no=- 123456 2 IN IP4 127.0.0.1...",
  "options": {
    "record": false
  }
}
```
- **Response (200 OK):**
```json
{
  "success": true,
  "call_id": "CALL_982341",
  "status": "ringing",
  "sdp_answer": "v=0\r\no=- 654321 2 IN IP4 127.0.0.1..."
}
```

#### Call Management Endpoints
- `GET /api/v1/calls`: List paginated call logs (`?direction=outbound&status=completed`).
- `GET /api/v1/calls/active`: List currently active VoIP calls.
- `GET /api/v1/calls/{callId}`: Inspect single call metadata, billing cost, and duration.
- `GET /api/v1/calls/statistics`: Calling usage analytics (`?period=month`).
- `POST /api/v1/calls/{callId}/answer`: Answer an incoming call.
- `POST /api/v1/calls/{callId}/reject`: Reject an incoming call (`reason: "busy"`).
- `POST /api/v1/calls/{callId}/end`: Terminate an active call.

### 6.2 Call Settings & Permissions (`/api/v1/whatsapp`)

- `POST /api/v1/whatsapp/{phoneNumberId}/settings`: Update business hours, recording, and consent configuration.
- `GET /api/v1/whatsapp/{phoneNumberId}/settings`: View current calling configuration.
- `POST /api/v1/whatsapp/calls/request-permission`: Send interactive WhatsApp message requesting call consent.
- `GET /api/v1/whatsapp/calls/permission/{contactId}`: Check consent status and expiration timestamp.
- `POST /api/v1/whatsapp/calls/generate-link`: Generate a direct WhatsApp click-to-call link.

---

## 7. Model Context Protocol (MCP) for AI Agents

- **Endpoint:** `POST /api/v1/mcp`
- **Specification:** [MCP Protocol 2024-11-05](https://modelcontextprotocol.io/specification/2024-11-05)
- **Transport:** Streamable HTTP (JSON-RPC 2.0)
- **Authentication:** `Authorization: Bearer <API_TOKEN>` and optional `X-Tenant-ID: <TEAM_ID>`.
- **Purpose:** Connect AI coding assistants and autonomous agents (Claude Desktop, Cursor, ChatGPT, custom LLMs) directly to WhatsApp messaging, contacts, calling, and automations within strict tenant security boundaries.

### 7.1 Supported JSON-RPC 2.0 Methods

| Method | Description | Response Pattern |
|---|---|---|
| `initialize` | Negotiates protocol version, server name, and capabilities | `result: { protocolVersion: "2024-11-05", serverInfo: { name: "WatxIO MCP Server", version: "1.0.0" }, capabilities: { tools: { listChanged: false } } }` |
| `ping` | Liveness check | `result: {}` |
| `notifications/initialized` | Client readiness signal | HTTP `202 Accepted` (no response body) |
| `tools/list` | Tool discovery manifest | `result: { tools: [...] }` (38 tool schemas) |
| `tools/call` | Invoke a specific tool | `result: { content: [{ type: "text", text: "..." }], isError: boolean }` |

### 7.2 Tool Directory (38 Tools by Category)

#### A. Conversational Messaging & Interactive Content (10 Tools)
- `send_message`: Send plain text WhatsApp messages (`to`, `message`).
- `send_template`: Send registered templates (`to`, `template_name`, `language`, `body_params`).
- `send_media`: Send media attachments (`to`, `type`: `image|video|audio|document`, `url`, `caption`).
- `send_interactive_buttons`: Send messages with up to 3 quick-reply buttons (`to`, `text`, `buttons`).
- `send_interactive_list`: Send interactive sectioned list pickers with up to 10 rows (`to`, `text`, `button_text`, `sections`).
- `send_carousel`: Send horizontal card carousels up to 10 cards (`phone`, `cards`).
- `send_flow`: Trigger interactive native WhatsApp Flows (`to`, `flow_id`, `headline`, `body`, `cta`, `mode`, `data`).
- `send_contact_card`: Share contact cards/vCards (`to`, `contact`).
- `send_location_request`: Request user live location (`to`, `text`).
- `mark_message_read`: Send read receipts for incoming messages (`message_id`).

#### B. Contact & Audience Management (7 Tools)
- `get_contact`: Fetch contact details, tags, and email by phone (`phone`).
- `get_contact_messages`: Fetch timeline of messages with direction and delivery timestamps (`phone`, `from`, `to`, `direction`, `limit`).
- `get_contact_activity`: Fetch audit events, lifecycle milestones, and tag changes (`phone`, `limit`).
- `search_contacts`: Search across name, phone, or email (`query`, `limit`).
- `upsert_contact`: Create or update contacts; auto-links Company model (`phone`, `name`, `email`, `company`).
- `get_contact_tags`: Discover all available tag definitions for the workspace.
- `tag_contact`: Attach or remove tags from a contact (`phone`, `tag_id`, `action`: `add|remove`).

#### C. Live Inbox & Conversation Concurrency (7 Tools)
- `list_conversations`: List active threads (`status`: `open|resolved|pending`, `limit`).
- `get_conversation_messages`: Retrieve chat transcript for a thread (`conversation_id`, `limit`).
- `close_conversation`: Resolve and archive conversation (`conversation_id`).
- `reopen_conversation`: Re-activate a closed conversation (`conversation_id`).
- `assign_conversation`: Assign conversation to any team member or team owner (`conversation_id`, `user_id`).
- `add_internal_note`: Add private agent note to thread (`conversation_id`, `content`).
- `toggle_ai`: Pause or resume bot auto-replies for human takeover (`conversation_id`, `enabled`: `boolean`).

#### D. Automations & Broadcast Campaigns (3 Tools)
- `list_automations`: List automation workflows available in tenant account (`active_only`).
- `start_automation`: Trigger an automation flow for a contact (`automation_id`, `phone`, `variables`).
- `list_campaigns`: Monitor broadcast campaigns and delivery stats (`status`, `limit`).

#### E. Templates & Canned Quick Replies (2 Tools)
- `get_templates`: Retrieve approved WhatsApp templates and parameter signatures (`search`).
- `get_canned_messages`: Fetch pre-written saved responses (`search`).

#### F. AI Assistance (1 Tool)
- `get_ai_suggest_reply`: Generate contextual AI suggestions based on recent conversation transcript without auto-sending (`conversation_id`).

#### G. WhatsApp Voice Calling (3 Tools)
- `initiate_call`: Start outbound VoIP call to contact (`to`).
- `end_call`: Terminate active call (`call_id`).
- `get_call_history`: Retrieve call logs, duration, and billing costs (`phone`, `limit`).

#### H. Workspace, Team & Analytics (4 Tools)
- `get_team_members`: Discover team members and user IDs for routing and assignments.
- `get_business_profile`: Retrieve WhatsApp Business profile settings (description, email, website).
- `get_dashboard_analytics`: Instant local database stats (sent, delivered, read rates, 7-day trend, wallet credits) (`range`: `7d|30d|90d`).
- `get_analytics`: Query Meta API conversation metrics across date ranges (`start`, `end`, `granularity`).

#### I. Webhooks (1 Tool)
- `trigger_webhook`: Dispatch active outbound webhook subscriptions (`webhook_id`, `event`, `payload`).

### 7.3 Sample MCP Invocations

#### A. Initialize Protocol
```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "method": "initialize"
}
```
**Response:**
```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "result": {
    "protocolVersion": "2024-11-05",
    "serverInfo": {
      "name": "WatxIO MCP Server",
      "version": "1.0.0"
    },
    "capabilities": {
      "tools": { "listChanged": false }
    }
  }
}
```

#### B. Tool Discovery (`tools/list`)
```json
{
  "jsonrpc": "2.0",
  "id": 2,
  "method": "tools/list"
}
```

#### C. Send Message Tool (`tools/call`)
```json
{
  "jsonrpc": "2.0",
  "id": 3,
  "method": "tools/call",
  "params": {
    "name": "send_message",
    "arguments": {
      "to": "+1234567890",
      "message": "Your table reservation is confirmed for 7:30 PM."
    }
  }
}
```
**Response:**
```json
{
  "jsonrpc": "2.0",
  "id": 3,
  "result": {
    "content": [
      {
        "type": "text",
        "text": "Message sent successfully. WhatsApp ID: wamid.HBgL..."
      }
    ],
    "isError": false
  }
}
```

### 7.4 Client Configuration (Claude Desktop & Remote Agents)

#### Claude Desktop Configuration (`claude_desktop_config.json`)
```json
{
  "mcpServers": {
    "watxio": {
      "command": "npx",
      "args": [
        "-y",
        "mcp-remote",
        "https://your-domain.com/api/v1/mcp",
        "--header",
        "Authorization: Bearer YOUR_API_TOKEN",
        "--header",
        "X-Tenant-ID: 1"
      ]
    }
  }
}
```
*Config file locations:*
- **macOS:** `~/Library/Application Support/Claude/claude_desktop_config.json`
- **Windows:** `%APPDATA%\Claude\claude_desktop_config.json`

---

## 8. Embedded Chat Widget Tokens

- **Method:** `POST /api/v1/embed-token`
- **Description:** Generates signed, short-lived JWT tokens to securely embed the live WhatsApp chat widget inside external customer portals, Shopify frontends, or partner dashboards.
- **Payload:**
```json
{
  "phone_number": "+1234567890",
  "name": "Jane Wilson",
  "permissions": ["read", "write"]
}
```
- **Response (200 OK):**
```json
{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "expires_at": "2026-08-10T16:22:00Z",
  "embed_url": "https://your-domain.com/embed/chat?token=eyJhbGci..."
}
```

---

## 9. Multi-Agent Conversation Concurrency & Locks

Prevents duplicate agent replies and collisions when multiple human agents or AI bots operate simultaneously on the same inbox.

- **Lock TTL:** 30 seconds (renewable via heartbeat).
- **Auto-Expiry:** Locks automatically expire if no heartbeat is received within 30 seconds.

### Endpoints:
- `POST /api/v1/conversations/{id}/lock`: Acquire exclusive lock. Returns `409 Conflict` if held by another agent.
- `POST /api/v1/conversations/{id}/heartbeat`: Extend lock by 30 seconds.
- `POST /api/v1/conversations/{id}/takeover`: Supervisor override to forcibly transfer lock.
- `POST /api/v1/conversations/{id}/unlock`: Release lock upon completing conversation.

---

## 10. Inbox Contact Resolution & Concurrency

Optimized for high-throughput live inboxes with optimistic locking.

- `GET /api/v1/inbox/contacts/resolve?phone=+1234567890`: Resolve single identity.
- `POST /api/v1/inbox/contacts/resolve-batch`: Resolve up to 100 phone numbers in a single query:
```json
{
  "phones": ["+1234567890", "+1987654321"]
}
```
- `PUT / PATCH /api/v1/inbox/contacts/{contact}`: Concurrency-safe contact update. Requires passing `version` integer. If another agent updated the record in the meantime, returns `409 Conflict`.
```json
{
  "version": 4,
  "name": "Jane Wilson-Smith",
  "custom_attributes": {
    "vip": true
  }
}
```
- `POST /api/v1/inbox/contacts/{contact}/assign`: Assign contact to a team agent (`user_id`).

---

## 11. E-Commerce Integrations & Catalog

Manage connected stores (Shopify, WooCommerce, Custom API) and sync product catalogs.

- `GET /api/v1/ecommerce/integrations/{integration}/health`: Health check and API credential validation.
- `POST /api/v1/ecommerce/integrations/{integration}/sync`: Trigger immediate product catalog import job.
- `GET /api/v1/ecommerce/integrations/{integration}/sessions`: View active abandoned cart checkout sessions.
- `PATCH /api/v1/ecommerce/integrations/{integration}/settings`: Update notification rules and cart recovery triggers.
- `POST /api/v1/products/{product}/lock`: Prevent automated e-commerce sync from overriding custom WhatsApp catalog edits.

---

## 12. Mobile Application Client API

Prefix: `/api/v1/mobile/*`

### 12.1 Authentication & Profile
- `POST /api/v1/mobile/auth/login`: Email & password login.
- `POST /api/v1/mobile/auth/send-otp`: Request SMS/WhatsApp 6-digit OTP code.
- `POST /api/v1/mobile/auth/login-otp`: Login with OTP.
- `GET /api/v1/mobile/auth/me`: Current user profile.
- `GET /api/v1/mobile/auth/teams`: List all available teams/workspaces.
- `POST /api/v1/mobile/auth/switch-team`: Switch active workspace (`team_id`).
- `POST /api/v1/mobile/auth/fcm-token`: Register Firebase Push Notification token.
- `POST /api/v1/mobile/auth/fcm-token/remove`: Unregister device push token.
- `POST /api/v1/mobile/auth/logout`: Revoke active mobile token.

### 12.2 Real-Time Presence
- `POST /api/v1/mobile/presence/heartbeat`: Broadcast agent active typing/viewing state.
- `POST /api/v1/mobile/presence/leave`: Clear presence upon exiting conversation.
- `POST /api/v1/mobile/broadcasting/auth`: Authorize private Pusher/Reverb channels.

### 12.3 Inbox & Messages
- `GET /api/v1/mobile/conversations`: Paginated list of conversations (`status: open|closed|all`, `assigned_to: me`).
- `GET /api/v1/mobile/conversations/{id}`: Conversation detail with contact profile.
- `GET /api/v1/mobile/conversations/{id}/messages`: Paginated message history.
- `POST /api/v1/mobile/conversations/{id}/messages`: Send text message.
- `POST /api/v1/mobile/conversations/{id}/send-template`: Send template message.
- `POST /api/v1/mobile/conversations/{id}/read`: Mark unread messages as read.
- `POST /api/v1/mobile/conversations/{id}/close` & `/reopen`: Toggle conversation status.
- `POST /api/v1/mobile/conversations/{id}/assign`: Assign conversation to any team member.
- `POST /api/v1/mobile/conversations/{id}/toggle-ai`: Toggle AI automated response bot.
- `POST /api/v1/mobile/conversations/{id}/ai-suggest`: Request AI-generated reply suggestions.
- `GET /api/v1/mobile/conversations/{id}/notes` & `POST .../notes`: Internal agent notes.
- `POST /api/v1/mobile/messages/{id}/star`: Star / unstar message.
- `POST /api/v1/mobile/messages/{id}/forward`: Forward message to another conversation.
- `POST /api/v1/mobile/messages/{id}/react`: Send emoji reaction (`emoji: "👍"`).
- `DELETE /api/v1/mobile/messages/{id}`: Delete message for agent.

### 12.4 Chunked Media Upload
Large media files (> 1 MB) are uploaded in ~700 KB binary chunks to safely bypass mobile network drops and reverse-proxy payload limits.
- `POST /api/v1/mobile/media/upload`: Single-request upload for small images/audio.
- `POST /api/v1/mobile/media/upload/chunk`:
  - `upload_id`: UUID for the file upload session.
  - `chunk_index`: 0-based chunk counter.
  - `total_chunks`: Total number of parts.
  - `file`: Raw binary slice.
  - The server automatically reassembles and validates the file upon receiving the final chunk.

### 12.5 Automations, Bots, Campaigns & Analytics
- `apiResource /api/v1/mobile/automations`: CRUD automation rules.
- `POST /api/v1/mobile/automations/{id}/toggle`: Enable / disable automation.
- `GET/POST /api/v1/mobile/bots`: Manage interactive keyword bots.
- `POST /api/v1/mobile/bots/{id}/duplicate`: Clone an existing bot workflow.
- `GET/POST /api/v1/mobile/campaigns`: Schedule and monitor broadcast campaigns.
- `GET /api/v1/mobile/analytics/dashboard`: View message volume, response times, and opt-in trends (`period: 7d|30d|90d`).
- `GET /api/v1/mobile/team/members`: List team members available for chat assignment.

---

## 13. Webhooks & Real-Time Event Subscriptions

### 13.1 Meta WhatsApp Inbound Webhooks

- **Webhook Verification:** `GET /api/webhook/whatsapp`
  - Validates `hub.mode`, `hub.verify_token`, and returns `hub.challenge`.
- **Event Receiver:** `POST /api/webhook/whatsapp`
  - Signed with Meta app secret via `X-Hub-Signature-256`.
  - Processes incoming customer messages, read receipts, delivery reports (`sent`, `delivered`, `read`, `failed`), and flow submissions.

### 13.2 Custom Inbound Webhook Sources (`/api/v1/webhooks/inbound/{source}`)

External systems (Shopify, WooCommerce, Stripe, Zapier, custom CRMs) post to dynamically configured webhook sources:
```bash
curl -X POST "https://your-domain.com/api/v1/webhooks/inbound/order-created" \
  -H "X-API-Key: YOUR_SOURCE_SECRET" \
  -H "Content-Type: application/json" \
  -d '{
    "order_id": "10982",
    "customer_phone": "+1234567890",
    "total": "99.00"
  }'
```

### 13.3 Outbound Webhooks (Platform to Your Server)

Subscribe your server to events generated inside the WhatsApp Business platform via `/developer/webhooks`.

#### Supported Outbound Events:
- `message.received`: Customer sent an incoming message.
- `message.status.updated`: Outbound message transitioned to `delivered`, `read`, or `failed`.
- `contact.created` / `contact.updated`: Contact identity or attributes changed.
- `contact.opted_out`: Customer opted out via keyword or STOP command.
- `call.initiated` / `call.ended`: VoIP call started or completed with duration/cost.
- `conversation.assigned`: Chat was assigned to a team member.
- `conversation.closed`: Conversation was marked resolved.

#### Verifying Outbound Webhook Signatures:
Every outbound POST delivery includes the header:
```http
X-Hub-Signature-256: sha256=32b8...
```
Compute the HMAC SHA-256 hash of the raw request body using your subscription secret:
```php
$calculatedSignature = 'sha256=' . hash_hmac('sha256', $rawPayload, $subscriptionSecret);
if (!hash_equals($calculatedSignature, $request->header('X-Hub-Signature-256'))) {
    abort(401, 'Invalid webhook signature');
}
```

---

## 14. Testing & Sandbox Mode

- **Sandbox Mode:** Toggle sandbox mode in `/developer` to simulate API calls and message deliveries without incurring Meta conversation fees.
- **Test WhatsApp Templates:** Use `POST /api/v1/mobile/templates/{template}/send-test` to test variable replacement with your own device before broadcasting.
