# WhatsApp Business Calling - API Documentation

## Overview

The WhatsApp Business Calling API enables voice calls through WhatsApp Business Platform. This document covers all calling-related endpoints, webhooks, and integration patterns.

---

## Authentication

All API requests require authentication using Laravel Sanctum tokens:

```http
Authorization: Bearer {your-api-token}
```

---

## Endpoints

### 1. Initiate Call

Start an outbound call to a contact.

**Endpoint**: `POST /api/calls/initiate`

**Request Body**:
```json
{
  "phone_number": "+1234567890",
  "options": {
    "caller_id": "optional_custom_id"
  }
}
```

**Success Response** (200):
```json
{
  "success": true,
  "call_id": "call_abc123",
  "status": "initiated",
  "message": "Call initiated successfully"
}
```

**Error Response** (400):
```json
{
  "success": false,
  "error": "Calling is not enabled for your account",
  "block_reason": "CALLING_NOT_ENABLED_FOR_NUMBER",
  "block_category": "phone_readiness"
}
```

---

### 2. Check Eligibility

Validate if a call can be initiated before attempting.

**Endpoint**: `POST /api/calls/check-eligibility`

**Request Body**:
```json
{
  "contact_id": 123
}
```

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "eligible": true,
    "blocked": false,
    "consent_type": "implicit",
    "checks": {
      "trigger_consent": { "passed": true },
      "phone_readiness": { "passed": true },
      "quality_rating": { "passed": true },
      "consent": { "passed": true },
      "agent_availability": { "passed": true },
      "usage_limits": { "passed": true }
    }
  }
}
```

**Blocked Response** (200):
```json
{
  "success": true,
  "data": {
    "eligible": false,
    "blocked": true,
    "block_reason": "CONTACT_OPTED_OUT",
    "user_message": "This contact has opted out of communications",
    "admin_message": "Contact opt-in status is not 'opted_in'"
  }
}
```

---

### 3. Answer Call

Accept an incoming call.

**Endpoint**: `POST /api/calls/{callId}/answer`

**Success Response** (200):
```json
{
  "success": true,
  "message": "Call answered successfully"
}
```

---

### 4. Reject Call

Reject an incoming call.

**Endpoint**: `POST /api/calls/{callId}/reject`

**Success Response** (200):
```json
{
  "success": true,
  "message": "Call rejected successfully"
}
```

---

### 5. End Call

Terminate an active call.

**Endpoint**: `POST /api/calls/{callId}/end`

**Success Response** (200):
```json
{
  "success": true,
  "message": "Call ended successfully"
}
```

---

### 6. Get Call History

Retrieve call logs with filtering and pagination.

**Endpoint**: `GET /api/calls`

**Query Parameters**:
- `direction` (optional): `inbound` or `outbound`
- `status` (optional): `completed`, `failed`, `rejected`, `missed`, `in_progress`
- `from_date` (optional): `YYYY-MM-DD`
- `to_date` (optional): `YYYY-MM-DD`
- `contact_id` (optional): Filter by contact
- `per_page` (optional): Results per page (default: 15, max: 100)
- `sort_by` (optional): Field to sort by (default: `created_at`)
- `sort_order` (optional): `asc` or `desc` (default: `desc`)

**Success Response** (200):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "call_id": "call_abc123",
      "direction": "outbound",
      "status": "completed",
      "from_number": "+1234567890",
      "to_number": "+0987654321",
      "duration_seconds": 300,
      "duration_formatted": "5:00",
      "cost_amount": 0.025,
      "cost_formatted": "$0.03",
      "initiated_at": "2026-01-26T10:00:00Z",
      "answered_at": "2026-01-26T10:00:05Z",
      "ended_at": "2026-01-26T10:05:05Z",
      "contact": {
        "id": 123,
        "name": "John Doe",
        "phone_number": "+0987654321"
      }
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 73
  }
}
```

---

### 7. Get Call Details

Retrieve detailed information about a specific call.

**Endpoint**: `GET /api/calls/{callId}`

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "call_id": "call_abc123",
    "direction": "outbound",
    "status": "completed",
    "from_number": "+1234567890",
    "to_number": "+0987654321",
    "duration_seconds": 300,
    "duration_formatted": "5:00",
    "cost_amount": 0.025,
    "cost_formatted": "$0.03",
    "initiated_at": "2026-01-26T10:00:00Z",
    "answered_at": "2026-01-26T10:00:05Z",
    "ended_at": "2026-01-26T10:05:05Z",
    "failure_reason": null,
    "contact": {
      "id": 123,
      "name": "John Doe",
      "phone_number": "+0987654321"
    },
    "conversation_id": 456
  }
}
```

---

### 8. Get Call Statistics

Retrieve call analytics and usage metrics.

**Endpoint**: `GET /api/calls/statistics`

**Query Parameters**:
- `period` (optional): `today`, `week`, `month`, `year` (default: `month`)

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "total_calls": 150,
    "inbound_calls": 60,
    "outbound_calls": 90,
    "completed_calls": 120,
    "failed_calls": 30,
    "total_duration_seconds": 18000,
    "total_duration_minutes": 300,
    "average_duration_seconds": 120,
    "total_cost": 1.50,
    "success_rate": 80.00,
    "usage_limits": {
      "allowed": true,
      "minutes_used": 300,
      "minutes_limit": 1000,
      "minutes_remaining": 700
    }
  }
}
```

---

### 9. Get Active Calls

Retrieve currently active calls.

**Endpoint**: `GET /api/calls/active`

**Success Response** (200):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "call_id": "call_xyz789",
      "direction": "inbound",
      "status": "ringing",
      "contact_name": "Jane Smith",
      "contact_phone": "+1122334455",
      "initiated_at": "2 minutes ago"
    }
  ]
}
```

---

### 10. Get Contact Call History

Retrieve call history for a specific contact.

**Endpoint**: `GET /api/calls/contacts/{contactId}/history`

**Query Parameters**:
- `limit` (optional): Number of calls to return (default: 50, max: 100)

**Success Response** (200):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "call_id": "call_abc123",
      "direction": "outbound",
      "status": "completed",
      "duration": "5:00",
      "cost": "$0.03",
      "initiated_at": "2026-01-26 10:00:00",
      "answered_at": "2026-01-26 10:00:05",
      "ended_at": "2026-01-26 10:05:05"
    }
  ]
}
```

---

## Webhooks

### Call Status Webhook

WhatsApp sends webhook events for call status updates.

**Endpoint**: `POST /api/webhook/whatsapp/calls`

**Verification**: `GET /api/webhook/whatsapp/calls?hub.mode=subscribe&hub.verify_token={token}&hub.challenge={challenge}`

**Webhook Payload**:
```json
{
  "entry": [
    {
      "id": "phone_number_id",
      "changes": [
        {
          "value": {
            "messaging_product": "whatsapp",
            "metadata": {
              "phone_number_id": "1234567890"
            },
            "calls": [
              {
                "call_id": "call_abc123",
                "from": "+1234567890",
                "to": "+0987654321",
                "timestamp": 1706265600,
                "status": "ringing",
                "duration_seconds": 0
              }
            ]
          },
          "field": "calls"
        }
      ]
    }
  ]
}
```

**Call Statuses**:
- `initiated` - Call has been initiated
- `ringing` - Phone is ringing
- `in_progress` - Call is active
- `completed` - Call ended successfully
- `failed` - Call failed to connect
- `rejected` - Call was rejected
- `missed` - Incoming call was not answered
- `no_answer` - Outbound call was not answered

---

## Error Codes

### Eligibility Block Reasons

| Code | Description | Category |
|------|-------------|----------|
| `CALLING_NOT_ENABLED_FOR_NUMBER` | Calling feature not enabled | phone_readiness |
| `PHONE_NUMBER_INACTIVE` | Phone number not active | phone_readiness |
| `ACCOUNT_FLAGGED_BY_META` | Account under review | quality |
| `QUALITY_RATING_TOO_LOW` | Quality rating is RED | quality |
| `CONTACT_OPTED_OUT` | Contact has opted out | consent |
| `NO_CALLING_CONSENT` | No explicit calling consent | consent |
| `CONSENT_EXPIRED` | Consent has expired | consent |
| `NO_AGENTS_AVAILABLE` | No agents online | agent |
| `ALL_AGENTS_BUSY` | All agents at capacity | agent |
| `MONTHLY_LIMIT_REACHED` | Monthly minutes exceeded | limits |
| `INVALID_TRIGGER_TYPE` | Invalid call trigger | trigger |
| `INVALID_TRIGGER_SOURCE` | Invalid call request source | trigger |
| `NO_CALL_KEYWORD_DETECTED` | No keywords found in message | trigger |
| `AUTOMATION_BLOCKS_CALLING` | Automation blocks calling | context |
| `NO_EXPLICIT_CONSENT` | Missing affirmative response | consent |
| `TEAM_CALLING_SUSPENDED` | Calling suspended due to missed calls | safeguards |
| `RATE_LIMIT_MINUTE_EXCEEDED` | Minutely rate limit exceeded | safeguards |
| `RATE_LIMIT_HOUR_EXCEEDED` | Hourly rate limit exceeded | safeguards |

---

## Agent Status & Routing

### 1. Update Agent Call Status

Manage an agent's availability for calls.

**Endpoint**: `POST /api/agents/status`

**Request Body**:
```json
{
  "call_status": "available|dnd",
  "is_call_enabled": true
}
```

### 2. Get Team Routing Config

Retrieve current call routing settings.

**Endpoint**: `GET /api/teams/settings/call-routing`

**Success Response**:
```json
{
  "mode": "round_robin",
  "role": "agent",
  "cooldown_seconds": 60,
  "ring_timeout_seconds": 30,
  "fallback_action": "auto_reply"
}
```

---

## Rate Limits

- **API Calls**: 600 requests per minute per team
- **Outbound Calls**: Based on team plan
- **Webhook Processing**: No limit (async processing)

---

## Best Practices

### 1. Pre-Check Eligibility
Always check eligibility before showing call button:
```javascript
const checkEligibility = async (contactId) => {
  const response = await fetch('/api/calls/check-eligibility', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ contact_id: contactId })
  });
  const data = await response.json();
  return data.data.eligible;
};
```

### 2. Handle Webhooks Asynchronously
Process webhook events in background jobs to avoid timeouts.

### 3. Implement Retry Logic
Retry failed calls with exponential backoff.

### 4. Monitor Usage
Track call minutes to avoid hitting limits.

### 5. Respect Consent
Always validate consent before initiating calls.

---

## Code Examples

### PHP (Laravel)
```php
use App\Services\CallService;

$team = auth()->user()->currentTeam;
$callService = new CallService($team);

$response = $callService->initiateCall('+1234567890');

if ($response['success']) {
    echo "Call initiated: " . $response['call_id'];
} else {
    echo "Error: " . $response['error'];
}
```

### JavaScript (Fetch API)
```javascript
const initiateCall = async (phoneNumber) => {
  const response = await fetch('/api/calls/initiate', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({ phone_number: phoneNumber })
  });
  
  const data = await response.json();
  
  if (data.success) {
    console.log('Call initiated:', data.call_id);
  } else {
    console.error('Error:', data.error);
  }
};
```

---

## Support

For API support, contact: support@example.com
