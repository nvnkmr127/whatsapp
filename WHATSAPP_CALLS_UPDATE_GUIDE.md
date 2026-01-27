# WhatsApp Call Settings Update Guide

Based on [Meta Developer Documentation](https://developers.facebook.com/documentation/business-messaging/whatsapp/calling/call-settings).

## Overview
WhatsApp Business Calling allows businesses to manage how customers can call them. This feature is not enabled by default and requires specific eligibility criteria (>= 2000 business-initiated conversations in a rolling 24h period).

## Configuration API
Call settings can be managed via the WhatsApp Business API.

### 1. Update Settings
**Endpoint**: `POST /<PHONE_NUMBER_ID>/settings`

**Parameters**:
- `status`: `enabled` or `disabled` (controls overall calling feature).
- `call_icon_visibility`: `show` or `hide` (controls call button visibility in chat/profile).
- `call_icons`: Object to show/hide based on country codes.
- `call_hours`: Define business hours, timezone, and holidays for call availability.
- `callback_permission_status`: `enabled` or `disabled` (for simplified user permission to call).
- `sip`: Configuration for SIP integration.

### 2. Retrieve Settings
**Endpoint**: `GET /<PHONE_NUMBER_ID>/settings`

**Parameters**:
- `include_sip_credentials`: `true` or `false` (to retrieve SIP details).

## Restrictions & Enforcement
- **User Feedback**: High negative feedback (blocks/reports) can trigger a temporary (7-day) suspension of calling features.
- **Low Pickup Rate**: If calls are not answered, the call button may be hidden. Warnings are sent via email and webhook.

## Webhooks
- `account_settings_update`: Triggered when call settings are changed.
- `account_update`: Triggered for enforcement actions (e.g., calling restrictions).

## Implementation Checklist
- [ ] Check Eligibility (Messaging Limits).
- [ ] Implement `POST` to configure Call Settings (Status, Hours, Icons).
- [ ] Implement `GET` to view current Call Settings.
- [ ] Handle `account_settings_update` and `account_update` webhooks.
