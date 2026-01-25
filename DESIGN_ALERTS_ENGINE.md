# Rule-Based Email Alerts Engine Design

## 1. Overview
A sophisticated system for managing, triggering, and delivering automated email alerts based on configurable rules. This engine handles throttling to prevent alert fatigue and implements escalation logic for critical incidents.

## 2. Core Components

### 2.1 Alert Definitions (`alert_rules` table)
This table defines the "what" and "how" of an alert.
- **`name`**: Friendly name (e.g., "High Database Latency").
- **`slug`**: Unique identifier (e.g., `db-latency-high`).
- **`alert_type`**: Enum (`SYSTEM`, `BILLING`, `SECURITY`, `ACCOUNT`).
- **`severity`**: Enum (`INFO`, `WARNING`, `CRITICAL`, `EMERGENCY`).
- **`trigger_conditions`**: JSON field defining thresholds (e.g., `{"metric": "latency", "operator": ">", "value": 500, "duration_seconds": 60}`).
- **`throttling_period`**: Integer (minutes). Don't send more than one alert of this type per period.
- **`escalation_path`**: JSON array of roles/users to notify if unresolved.

### 2.2 Alert Logs (`alert_logs` table)
Tracks every time a rule matches and an alert is processed.
- **`rule_id`**: Foreign key to `alert_rules`.
- **`recipient`**: Target email address.
- **`payload`**: JSON snapshot of the data that triggered the alert.
- **`status`**: `sent`, `throttled`, `escalated`, `resolved`.
- **`suppression_key`**: Hash used for throttling (e.g., `md5(rule_id + target_resource_id)`).

## 3. Throttling Logic

To prevent "Email Storms", the engine uses a **Suppression Key** mechanism:
1. When an alert is triggered, generate a `suppression_key` (e.g., `db-down-server-1`).
2. Check `alert_logs` for any record with the same key within the `throttling_period`.
3. If exists, log as `throttled` and skip sending.
4. If not exists, send email and log as `sent`.

## 4. Escalation Logic

For `CRITICAL` and `EMERGENCY` alerts:
1. **Initial Trigger**: Send email to Level 1 (e.g., Team Member).
2. **Acknowledge Checker**: A scheduled job checks for unacknowledged critical alerts.
3. **Level 2 (T+30m)**: If still active and unacknowledged, send to Level 2 (e.g., Team Owner).
4. **Level 3 (T+2h)**: Send to System Administrator.

## 5. Integration Workflow

```php
// Example: Triggering an alert from code
AlertEngine::trigger('billing.limit_reached', [
    'team_id' => $team->id,
    'current_usage' => $usage,
    'limit' => $limit
]);

// Internal Logic
public function trigger(string $ruleSlug, array $data) {
    $rule = AlertRule::where('slug', $ruleSlug)->first();
    
    // 1. Check Throttling
    if ($this->isThrottled($rule, $data)) {
        return;
    }
    
    // 2. Render Template
    $content = $this->templateService->render($rule->template_slug, $data);
    
    // 3. Dispatch Email
    $this->centralEmailService->send($rule->recipient, $content);
    
    // 4. Log & Schedule Escalation if Critical
    $log = $this->logAlert($rule, $data);
    if ($rule->isCritical()) {
        ScheduleEscalation::dispatch($log)->delay(now()->addMinutes(30));
    }
}
```

## 6. Alert Types & Hierarchy

| Type | Example | Default Throttling | Recipient |
| :--- | :--- | :--- | :--- |
| **Security** | Brute force login | 1 hour | Admin + User |
| **System** | Disk space < 10% | 4 hours | SRE Team |
| **Billing** | Credit exhausted | 24 hours | Account Owner |
| **Operational** | API Key expired | 12 hours | Developer |

## 7. Escalation Tiers

- **Tier 1 (Instant)**: Email to the direct stakeholder.
- **Tier 2 (Delayed)**: Escalation email to manager if not acknowledged.
- **Tier 3 (Urgent)**: Notification to the Platform Emergency Mailbox.
