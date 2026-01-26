# WhatsApp Business Calling - User Guide

## Table of Contents
1. [Getting Started](#getting-started)
2. [Making Calls](#making-calls)
3. [Receiving Calls](#receiving-calls)
4. [Call History](#call-history)
5. [Analytics & Billing](#analytics--billing)
6. [Troubleshooting](#troubleshooting)

---

## Getting Started

### Prerequisites
- WhatsApp Business Account verified
- Calling feature enabled by administrator
- Active subscription with call minutes
- Contact must have opted in

### Enabling Calling
1. Navigate to **Settings** → **WhatsApp Configuration**
2. Ensure your phone number is verified
3. Enable "Voice Calling" feature
4. Set monthly call minute limits (optional)

---

## Making Calls

### From Contact View

1. **Open a Contact**
   - Navigate to **Contacts** → Select contact
   - Or open an active conversation

2. **Check Eligibility**
   - Look for the **Call** button
   - If disabled, hover to see reason
   - Green button = Ready to call
   - Red message = Blocked (see reason)

3. **Initiate Call**
   - Click **Call Contact** button
   - Wait for "Calling..." status
   - Call will ring on contact's WhatsApp

4. **During Call**
   - See call duration timer
   - Click **End Call** to terminate
   - Call automatically ends when contact hangs up

### From Chat Window

1. **Active Conversation**
   - Open conversation with contact
   - Look for call icon in header
   - Click to initiate call

2. **User Requests Call**
   - If user sends "call me" or similar
   - System detects request automatically
   - Call button becomes available

### Call Eligibility

Your call may be blocked if:
- ❌ Contact has opted out
- ❌ Contact declined calling previously
- ❌ No conversation history
- ❌ Outside 24-hour message window (without explicit consent)
- ❌ No call request detected (for user-initiated calls)
- ❌ Consent expired or not affirmative (for agent-offered calls)
- ❌ Monthly call limit reached
- ❌ No agents available
- ❌ Quality rating too low
- ❌ Blocked by current automation state

**Tip**: Always check eligibility status before attempting calls.

---

## Receiving Calls

### Incoming Call Notification

1. **Call Alert**
   - Browser notification appears
   - Ringtone plays (if enabled)
   - Call card shows in interface

2. **Call Information**
   - Contact name (if saved)
   - Phone number
   - Incoming call indicator

3. **Answer or Reject**
   - Click **Answer** (green button)
   - Click **Reject** (red button)
   - Missed calls logged automatically

### During Incoming Call

1. **Answer Call**
   - Click Answer button
   - Call connects immediately
   - Timer starts

2. **Reject Call**
   - Click Reject button
   - Call ends
   - Contact sees "Call Rejected"

---

## Call Lifecycle & Tracking

Every call follows a structured lifecycle to ensure accurate reporting and real-time updates:

### Lifecycle States

- **Offered**: The initial request for a call (inbound or outbound).
- **Ringing**: The recipient's device is actively alerting them of the call.
- **Answered**: The call is connected and the conversation is ongoing.
- **Ended**: The call has finished successfully (completed).
- **Missed**: The call was not answered within the timeout period.
- **Rejected**: The recipient manually declined the call.
- **Failed**: A technical error prevented the call from connecting.

### Real-Time Updates
The system broadcasts real-time events for every state transition, allowing the dashboard to update instantly without refreshing the page.

### Conversation Timeline
Terminal call events (Completed, Missed, etc.) are automatically logged in the chat timeline as system messages. This allows agents to see the full interaction history in one place.

### Post-Call Interaction
Agents can add internal notes to any call log entry. These notes are linked to the contact and are visible in the "Notes" tab of the inbox.

---

## Calling Safeguards & Reliability

To ensure high service quality and protect your WhatsApp account rating, the system enforces several safeguards:

### Rate Limiting
Outbound calls are subject to rate limits to prevent anti-spam triggers:
- **Minutely Limit**: Default 5 calls per minute.
- **Hourly Limit**: Default 60 calls per hour.

### Auto-Suspension (Missed Call Protection)
If the team misses too many calls in a short period (e.g., 5 missed calls in 30 minutes), calling features will be **automatically suspended** for 15 minutes. This allows the team to regroup and ensures customers aren't left ringing without a response.

### Quality Rating Protection
The system monitors your Meta Quality Rating. If the rating drops to **RED** (High Risk), calling will be disabled until the rating improves.

---

## Agent Availability & Routing

### Call Availability Status

Agents can manage their availability for calls separately from their chat availability:

- 🟢 **Available**: Ready to receive routed calls.
- 🔴 **Busy**: Currently on an active call.
- 🟡 **Cooldown**: Recently finished a call (auto-switches to Available after 60s).
- ⚪ **DND**: Do Not Disturb - blocked from new calls.
- ⚫ **Offline**: Not active - blocked from new calls.

### Routing Modes

The system distributes calls based on the team's configuration:

1. **Sticky Assignment**: Prioritizes the agent who is currently assigned to the contact.
2. **Round-Robin**: Distributes calls equally among available agents based on their last call time.
3. **Role-Based**: Notifies all available agents with a specific role (e.g., "Support").
4. **Broadcast**: Notifies all available agents simultaneously; the first to answer gets the call.

### Missed Call Fallback

If no agents answer within the timeout, the system follows fallback rules:
- **Auto-Reply**: Sends a WhatsApp message promising a callback.
- **Escalation**: Redirects to a manager or secondary group.
- **Voicemail**: Prompts the user to leave a message.

---

## Call History

### Viewing Call Logs

1. **Navigate to Call History**
   - Click **Calls** in main menu
   - Or go to `/calls` page

2. **Filter Calls**
   - **Direction**: Inbound / Outbound
   - **Status**: Completed / Failed / Missed
   - **Date Range**: From / To dates
   - **Search**: By contact name or number

3. **Call Details**
   - Direction (↓ In / ↑ Out)
   - Contact information
   - Status badge (color-coded)
   - Duration (MM:SS)
   - Cost
   - Date & Time

### Call Statistics

**Summary Cards** show:
- Total Calls (this month)
- Success Rate (%)
- Total Minutes
- Total Cost

### Exporting Call Data

1. Apply desired filters
2. Click **Export** button
3. Download CSV file
4. Open in Excel/Sheets

---

## Analytics & Billing

### Call Analytics Dashboard

Access: **Calls** → **Analytics**

**Metrics Available**:
- Total calls by period
- Success vs failure rate
- Inbound vs outbound breakdown
- Cost breakdown (30-day chart)
- Top contacts by call volume
- Average call duration

### Usage Limits

**Monthly Limit Alert**:
- Shows minutes used / limit
- Progress bar visualization
- Minutes remaining
- Warning when >80% used

**Example**:
```
Monthly Usage: 450 / 1000 min
[████████░░] 45%
550 minutes remaining
```

### Call Costs

**Pricing** (example):
- $0.005 per minute
- Rounded up to nearest minute
- Billed at call completion

**Cost Calculation**:
- 5:30 call = 6 minutes = $0.03
- 0:45 call = 1 minute = $0.005

**Viewing Costs**:
1. Go to **Billing** → **Usage**
2. See "Call Minutes" section
3. View detailed breakdown
4. Download invoice

---

## Troubleshooting

### Call Button Disabled

**Problem**: Call button is grayed out

**Solutions**:
1. Check if contact has opted in
2. Verify you have an active conversation
3. Ensure within 24-hour message window
4. Check monthly limit not exceeded
5. Confirm agents are available

### Call Failed to Connect

**Problem**: Call initiated but didn't connect

**Possible Causes**:
- Contact's WhatsApp not active
- Poor internet connection
- Contact blocked your business
- Phone number invalid

**Solutions**:
1. Verify contact's phone number
2. Check internet connection
3. Try again after a few minutes
4. Contact support if persistent

### No Incoming Call Notification

**Problem**: Missed incoming calls

**Solutions**:
1. Enable browser notifications
2. Check notification permissions
3. Ensure tab is not muted
4. Keep browser window open
5. Check "Do Not Disturb" settings

### Quality Rating Warning

**Problem**: "Quality rating is medium" warning

**What it means**:
- Your account quality is YELLOW
- Calls still allowed but monitored
- Risk of restrictions if worsens

**Actions**:
1. Reduce spam/unsolicited calls
2. Only call opted-in contacts
3. Improve response times
4. Monitor block/report rates

### Monthly Limit Reached

**Problem**: "Monthly call limit reached"

**Solutions**:
1. Wait until next month (auto-resets)
2. Upgrade to higher plan
3. Purchase additional minutes
4. Contact sales for custom limits

### Consent Expired

**Problem**: "Call consent has expired"

**What it means**:
- User's consent is >12 months old (long-term) or >1 hour (agent offer)
- Need to renew consent

**Solutions**:
1. Request call again (user-initiated)
2. Send new call offer (agent-offered)
3. User must respond affirmatively (e.g., "Yes", "OK")
4. Update long-term consent in contact settings

---

## Best Practices

### ✅ DO:
- Always get explicit consent before calling
- Respect user's "don't call" preferences
- Call during business hours
- Keep calls professional and brief
- Log call notes for reference

### ❌ DON'T:
- Call opted-out contacts
- Make unsolicited sales calls
- Call outside business hours (unless urgent)
- Ignore quality rating warnings
- Exceed monthly limits

---

## Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| `Ctrl + K` | Initiate call (when eligible) |
| `Ctrl + E` | End active call |
| `Ctrl + H` | View call history |
| `Esc` | Reject incoming call |

---

## FAQs

**Q: Can I call any WhatsApp number?**
A: No, only contacts who have opted in and given calling consent.

**Q: Are calls free?**
A: Calls are charged per minute based on your plan.

**Q: Can I schedule callbacks?**
A: Yes, use the "Schedule Callback" feature in contact view.

**Q: What happens if I miss a call?**
A: It's logged in call history. You can call back from there.

**Q: Can I record calls?**
A: Call recording depends on local laws and must be disclosed.

**Q: How do I improve my quality rating?**
A: Reduce spam, get consent, respond quickly, avoid blocks/reports.

---

## Support

**Need Help?**
- Email: support@example.com
- Chat: Click support icon
- Documentation: /docs
- Status: status.example.com

**Report Issues**:
- Bug reports: bugs@example.com
- Feature requests: feedback@example.com
