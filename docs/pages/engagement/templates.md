# Template Manager

## What is it?
The **Template Manager** is where you create and manage **WhatsApp Message Templates**. Meta requires business-initiated messages to be pre-approved templates to prevent spam.

## Why is it useful?
- **Compliance**: Ensures your messages follow WhatsApp's policies.
- **Speed**: Use pre-written messages for common updates (e.g., "Your order is ready").
- **Rich Media**: Create templates with Headers (Images/PDFs), Footers, and Interactive Buttons (Quick Replies/Call-to-Action).

## Option Buttons (UI Guide)
- **Sync Templates**: Pulls the ongoing status (Approved/Rejected) from Meta.
- **New Template**: Opens the creation wizard.
- **Status Icons**:
  - **Green**: Approved (Ready to use).
  - **Yellow**: Pending (Meta is reviewing).
  - **Red**: Rejected (Violates policy).
- **Preview**: Review how the template looks on a device.

## Use Cases
1.  **Shipping Update**: You create a template: "Hi {{1}}, your order {{2}} has shipped!". Meta approves it, and you connect it to your automated shipping system.
2.  **24-Hour Re-engagement**: A customer hasn't replied in 2 days. The chat window is locked. You send a template "Are you still interested?" to re-open the 24-hour window.

## Logic Flow
| Feature | Actor | Trigger | Flow | Business Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **Approval** | Admin | Create Template | 1. Admin designs message.<br>2. System sends to Meta API.<br>3. Meta AI reviews text.<br>4. Status updates to "Approved". | Business gains permission to message customers proactively. |

## How to Use
1.  **Navigate**: Go to **Engagement** > **Templates**.
2.  **Sync**: Always click **Sync Templates** first to get the latest data.
3.  **Create**: Click **New Template**.
4.  **Design**:
    - **Category**: typically "Marketing" or "Utility".
    - **Body**: Write text with variables like `{{1}}` for dynamic data.
    - **Buttons**: Add "Visit Website" or "Yes/No" buttons.
5.  **Submit**: Send for review. It usually takes 1-5 minutes for approval.
