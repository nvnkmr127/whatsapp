# Shared Team Inbox

## What is it?
The **Shared Team Inbox** is a centralized chat interface that allows multiple agents to view, manage, and reply to messages from a single WhatsApp Business number. Instead of a single phone device, your entire support team logs into this dashboard to handle customer conversations in real-time.

## Why is it useful?
- **No Device Dependency**: You don't need a physical phone kept charged and online.
- **Team Collaboration**: Multiple staff members can answer queries simultaneously, reducing wait times.
- **Context & History**: Every conversation is stored centrally, so any agent can pick up where another left off.
- **Rich Media**: Send images, videos, documents, and audio notes just like on a personal phone.

## Option Buttons (UI Guide)
- **Chat List (Left Panel)**: Displays all active conversations.
  - **Unread Badge**: Red counter showing new messages.
  - **Filter Icon**: Sort by "Unread", "All", or "Assigned to Me".
- **Message Input (Bottom Center)**:
  - **Text Box**: Type your reply here.
  - **Attachment Icon (Paperclip)**: Send images, PDFs, or videos.
  - **Emoji Icon**: Insert emojis.
  - **Voice Note (Mic)**: Record and send audio messages.
- **Action Bar (Top Right)**:
  - **Assign Agent**: Dropdown to assign the chat to a specific team member.
  - **Close Chat**: Mark the conversation as "Resolved".
  - **Template Icon**: Required to verify a conversation if 24 hours have passed since the user's last message.

## Use Cases
1.  **Customer Support**: A customer asks, "Where is my order?" An agent instantly checks the order status and replies.
2.  **Sales Inquiry**: A potential client asks for a brochure. Sales staff attaches the PDF brochure and sends it immediately.
3.  **Handover**: An agent is going off-shift. They assign their active chats to the next shift's agent so no customer is ignored.

## Logic Flow
| Feature | Actor | Trigger | Flow | Business Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **Team Inbox** | Support Agent | Customer sends a message | 1. Message appears in "Unread" list.<br>2. Agent opens chat.<br>3. Agent types reply or attaches file.<br>4. Agent clicks "Send". | Customer query resolved instantly; Improved satisfaction. |

## How to Use
1.  **Navigate to Inbox**: Click **Shared Inbox** in the sidebar.
2.  **Select a Chat**: Click on any conversation from the left-hand list.
3.  **Read & Reply**: Read the customer's message in the central view. Type your answer in the box at the bottom.
4.  **Send Media (Optional)**: Click the **Paperclip icon**, select your file, and hit send.
5.  **Re-initiate (If needed)**: If the text box is locked (24-hour rule), click the **Template Icon** to select a pre-approved message to restart the conversation.
