---
description: Safe Publish Workflow for Automations
---

# Publishing an Automation Flow

This workflow ensures safe, validated deployment of automation flows with proper versioning and risk assessment.

## Pre-Publish Validation

1. **Automatic Validation**: The system runs validation automatically when you click "Publish Flow"
   - All nodes are checked for required fields
   - Flow structure is analyzed for loops and unreachable nodes
   - External dependencies are verified (API keys, templates, etc.)

2. **Validation Gating**: If critical errors exist:
   - The Publish button turns RED with warning icon
   - Text changes to "Fix Errors to Publish"
   - Clicking opens the Preflight Checklist
   - You CANNOT proceed until all errors are resolved

## Publish Review Modal

Once validation passes, clicking "Publish Flow" opens a comprehensive review screen:

### Summary Metrics
- **Node Count**: Total automation steps
- **Trigger Type**: What initiates this flow
- **Validation Status**: Green checkmark confirming readiness

### Risk Assessment
The system automatically identifies potential risks:

**High Risk (Red)**:
- Broad triggers (e.g., "User Starts Conversation") that fire for ALL contacts
- Requires careful consideration before enabling

**Medium Risk (Amber)**:
- External dependencies (OpenAI, Webhooks) that can fail or incur costs
- May need monitoring after deployment

**Low Risk (Gray)**:
- Large flows (>15 nodes) that might be harder to debug
- Informational only

### Version Notes
- **Required Field**: Describe what changed in this version
- Examples: "Added welcome branch", "Fixed delay timing", "Optimized AI prompts"
- Stored in version history for team reference

## Versioning System

### Automatic Version Increment
- Each publish creates a new version (v1 → v2 → v3)
- Previous versions are logged with:
  - Version number
  - Publication notes
  - Timestamp
  - Publisher name

### Version History (Right Sidebar)
When no node is selected, the right sidebar shows:
- **Active Status**: Live/Draft indicator with current version
- **Version Timeline**: Chronological list of all publications
- Click any version entry to see what changed

## Publishing Process

1. Click **"Publish Flow"** button (top-right toolbar)
2. Review the **Summary Screen**:
   - Verify node count and trigger type
   - Read all risk warnings carefully
   - Add descriptive version notes
3. Click **"Go Live Now"** to confirm
4. Flow immediately becomes active for matching triggers

## Safety Features

### Validation Gating
- Cannot publish with critical errors
- Warnings are shown but don't block publishing
- All issues are listed in the Preflight Checklist

### Risk Warnings
- High-impact triggers are flagged
- External dependencies are highlighted
- Complex flows are noted

### Version Control
- Every publish is logged
- Team can see who published what and when
- Notes provide context for changes

### Rollback Capability
- Version history preserved
- Can reference previous configurations
- (Future: One-click rollback to previous version)

## Best Practices

1. **Test Before Publishing**:
   - Use "Save as Draft" frequently while building
   - Test with a small contact segment first
   - Verify all external integrations work

2. **Write Clear Version Notes**:
   - Describe what changed
   - Note any new dependencies
   - Mention expected behavior changes

3. **Monitor After Publishing**:
   - Check automation runs in the first hour
   - Watch for error rates
   - Verify triggers are firing correctly

4. **Review Risk Warnings**:
   - Don't ignore high-risk warnings
   - Ensure you understand the impact
   - Consider limiting scope if needed

## Troubleshooting

**"Fix Errors to Publish" button won't go away**:
- Check the Preflight Checklist (bottom-left)
- Click each issue to jump to the problematic node
- Fix all errors marked in red

**Risk warnings concern me**:
- Warnings don't block publishing
- They're informational to help you make informed decisions
- Consider adjusting trigger scope or adding conditions

**Version history not showing**:
- History only appears after first publish
- Draft saves don't create versions
- Only "Publish Flow" increments version number

## Technical Details

### Database Fields
- `version`: Integer, auto-incremented on publish
- `last_published_at`: Timestamp of most recent publish
- `publish_log`: JSON array of version history
- `is_active`: Boolean, set to true on publish

### Validation Service
- Located: `app/Services/AutomationValidationService.php`
- Checks: Structure, content, dependencies, connectivity
- Returns: Issues array with level (error/warning) and field mapping
