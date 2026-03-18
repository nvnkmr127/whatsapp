# Terminology Simplification - Simple English Update

**Date:** March 18, 2026  
**Scope:** Full system terminology update for business user clarity  
**Target Audience:** Non-technical business users

---

## Overview

All technical terminology throughout the system has been simplified to use plain, everyday English. This makes the platform more accessible to business users who may not be familiar with technical jargon.

---

## UI Label Changes

### Automation Analytics Page

| Old Term | New Term | Context |
|----------|----------|---------|
| "Completion Rate" | "Success Rate" | Flow completion percentage card |
| "completed / runs" | "successful / total flows executed" | Flow completion subtitle |
| "Failure Rate" | "Failed %" | Flow failure percentage card |
| "failed runs" | "flows did not complete" | Failed flow subtitle |
| "Most Common Failure Node" | "Most Common Failed Step" | Problem identification card |
| "failures" | "times failed" | Failure count subtitle |
| "No failed nodes" | "No failed steps" | Empty state message |
| "Healthy execution pattern so far" | "Flow is running smoothly" | Success message |
| "Attributed Revenue" | "Revenue Generated" | Revenue card title |
| "-day window after run start" | "days after flow started" | Revenue window description |
| "Automation Message Status Report" | "Message Status Report" | Report section title |
| "Sent / Delivered / Read / Failed..." | "Track message delivery and engagement for this flow." | Report description |
| "Delivery Rate:" | "Delivered:" | Performance metric label |
| "Read Rate:" | "Read:" | Engagement metric label |
| "Funnel Drop-Off By Step" | "Step Performance" | Analytics section title |
| "Contacts that reached each node..." | "See which steps contacts reach where people drop off." | Section description (improved clarity) |
| "Export Step Contacts" | "Download Step Contacts" | Button label (more action-oriented) |
| "No outbound message status data..." | "No message activity for this flow..." | Empty state message |
| "Based on successful node transitions..." | "How long contacts typically spend between steps..." | Timing section subtitle |

### Analytics Dashboard Page

| Old Term | New Term | Context |
|----------|----------|---------|
| "Export Webhook CSV" | "Download Delivery Report" | Export button |
| "No outbound webhook status records..." | "No message activity for this period." | Empty state message |
| "Webhook Delivery Report" | "Message Delivery Report" | Report section title (future update) |

### Webhook Sources / Incoming Messages Page

| Old Term | New Term | Context |
|----------|----------|---------|
| "Webhook Sources" | "Incoming Messages" | Page title |
| "Configure webhooks from external platforms..." | "Track messages received from external platforms..." | Page description |
| "Active Webhook Sources" | "Incoming Message Sources" | Section header |
| "Manage your existing integrations" | "Track where messages are coming from" | Section description |
| "Export Webhook CSV" | "Download All Messages" | Team-level export button |
| "Export Failed CSV" | "Download Failed Messages" | Failed messages export button |
| "Webhook History" | "Activity Log" | Navigation link |

---

## Code Comments & Documentation

### Property Names (with added comments)

```php
// AutomationAnalytics.php
public int $dateRange = 30; // Number of days to look back in analytics
public string $messageStatusFilter = 'all'; // Filter: all, sent, delivered, read, or failed
public int $messageDetailsLimit = 100; // Max rows to show in message table
public string $stepNodeFilter = 'all'; // Selected step to filter contacts by
public array $dashboard = []; // Contains flow performance data
public string $currencySymbol = '$'; // Currency symbol for revenue display
```

```php
// WebhookSourceManager.php
public $exportDateRange = 30; // Days back to include in exports (7, 30, or 90)
public $exportStatusFilter = 'all'; // Filter exports by message status
// Wizard State - tracks the step-by-step configuration process
```

### Method Comments

- `exportMessageReport()` - "Download all messages sent through this flow as a CSV file filtered by date and status"
- `exportStepContactsReport()` - "Download list of all contacts that reached each step in this flow"
- `refreshData()` - "Reload all flow performance data from the database"

---

## Database Column Terminology (Internal Use)

**Note:** Database column names remain technical for consistency and query efficiency. Internal references use technical names:
- `automation_id` → refers to a "flow"
- `automation_run_id` → refers to a "session" in documentation
- `webhook_source_id` → refers to a "source" or "incoming message source"
- `node_id` → refers to a "step" in user-facing UI

---

## User-Facing Concepts Mapping

| Technical Concept | Simple English Term | Where It Appears |
|------------------|-------------------|------------------|
| Webhook | Incoming message or external message source | Page titles, section headers |
| Node | Step | UI labels, tables, filters |
| Run/Execution | Flow execution or session | Cards, descriptions, export data |
| Automation | Flow | Page titles, reports |
| Status (sent/delivered/read/failed) | Kept short (Sent, Delivered, Read, Failed) | Status indicators, filters |
| Drop-off | Drop-off (kept as-is, it's intuitive) | Analytics tables |
| Ledger | Not user-visible; internal only | Code/comments only |

---

## Filter Options (Remain Clear)

- Date Range: "7d" / "30d" / "90d" (simple and clear, kept as-is)
- Message Status: "All" / "Sent" / "Delivered" / "Read" / "Failed" (already simple)
- Steps: "All Steps" / [specific step names] (already user-friendly)

---

## Communication Guidelines

When explaining system features to business users:

❌ **Avoid:** "Export the webhook status report with node-level filtering"  
✅ **Say:** "Download the message delivery report filtered by step"

❌ **Avoid:** "Check the automation run's failure rate"  
✅ **Say:** "See how many flows didn't complete"

❌ **Avoid:** "Configure the ledger transformation rules"  
✅ **Say:** "Set up how messages should be processed"

---

## Files Modified

1. **App/Livewire/Automations/AutomationAnalytics.php**
   - Added clear comments to properties
   - Added documentation to export methods

2. **Resources/Views/Livewire/Automations/automation-analytics.blade.php**
   - Updated all UI labels to simple English
   - Improved section titles and descriptions
   - Simplified empty state messages

3. **Resources/Views/Livewire/Analytics/analytics-dashboard.blade.php**
   - Updated export button labels
   - Improved empty state messaging

4. **Resources/Views/Livewire/Developer/webhook-source-manager.blade.php**
   - Renamed page section from "Webhook Sources" to "Incoming Messages"
   - Updated export button labels
   - Improved section titles

5. **App/Livewire/Developer/WebhookSourceManager.php**
   - Added clear comments to export properties

---

## Impact

- ✅ **User Experience:** Non-technical users can now understand reports without jargon
- ✅ **Accessibility:** Simpler language makes the system more inclusive
- ✅ **Support:** Support team can use familiar terms with customers
- ✅ **Documentation:** External docs can now focus on business value vs. technical details

---

## Version

**System Version:** March 2026  
**Update Type:** UI/UX Terminology Simplification  
**Status:** Complete and tested
