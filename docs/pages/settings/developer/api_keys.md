# API Keys

## What is it?
**API Keys** are "Passwords for Robots". They allow your external software (like a Python script or Zapier) to log in to your account and perform actions authorized by you.

## Why is it useful?
- **Automation**: Build custom scripts to "Get all contacts" or "Send Broadcast".
- **Security**: You can specific permissions (e.g., "Read Only"). If a key is leaked, you can delete just that key without changing your main password.

## Option Buttons (UI Guide)
- **Create Token**: Generate a new secret key.
- **Permissions**: Select scope (e.g., `read`, `create`, `update`, `delete`).
- **Revoke (Delete)**: Instantly disable a key so it stops working.

## Use Cases
1.  **Metric Dashboard**: You build a custom TV dashboard for your office. You create a "Read Only" API key so the dashboard can fetch stats every minute securely.

## Logic Flow
| Feature | Actor | Trigger | Flow | Business Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **Authentication** | System | API Request | 1. External App sends request with `Bearer {token}` header.<br>2. System validates token exists and has permission.<br>3. Request proceeds. | Secure programmatic access. |

## How to Use
1.  **Navigate**: Go to **Developer** > **API Keys**.
2.  **Add**: Click **Create Token**.
3.  **Name**: "Zapier Integration".
4.  **Copy**: **IMPORTANT**: Copy the key immediately. It will never be shown again.
