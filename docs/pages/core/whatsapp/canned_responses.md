# Canned Responses

## What is it?
**Canned Responses** (or Saved Replies) are pre-defined message templates that your agents can instantly insert into a chat. They act as shortcuts for common questions, greetings, or explanations, saving time and ensuring consistent communication across your team.

## Why is it useful?
-   **Speed**: Agents don't have to type the same "Pricing" or "Welcome" message 50 times a day.
-   **Consistency**: Ensure every agent uses the exact approved wording for sensitive topics (like refunds or policy).
-   **professionalism**: Avoid typos in common messages.

## Interface Guide
-   **Shortcut**: A short keyword (like `intro` or `price`) that acts as a trigger.
-   **Content**: The full message text that will be sent to the customer.

## Use Cases
1.  **Greetings**: A standard "Hello, thanks for contacting [Company]!" welcome message.
2.  **FAQs**: Detailed answers to "What are your hours?" or "Where are you located?".
3.  **Handoffs**: A polite "I'm transferring you to a specialist now" message.

## Logic Flow
| Feature | Actor | Trigger | Flow | Business Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **Insert Reply** | Agent | Customer asks common question | 1. Agent types `/` in chat.<br>2. Selects `hours` from list.<br>3. Message auto-fills.<br>4. Agent sends. | Response time < 5s; Accurate info delivered. |

## How to Use
### Creating a Response
1.  Go to **WhatsApp API > Canned Responses**.
2.  Click **Create Response**.
3.  **Shortcut**: Enter a simple keyword (e.g., `refund`).
4.  **Content**: Type the full explanation.
5.  Click **Save Response**.

### Using in Chat
1.  Open any conversation in the **Shared Inbox**.
2.  In the message input, type `/`.
3.  A list of your saved replies will appear.
4.  Click one (or keep typing to filter, e.g., `/ref...`) to insert it.
