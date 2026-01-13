# User Guide

## Table of Contents
1. [Getting Started](#1-getting-started)
2. [Connecting WhatsApp](#2-connecting-whatsapp)
3. [Managing Contacts](#3-managing-contacts)
4. [Using the Shared Inbox](#4-using-the-shared-inbox)
5. [Creating Automations](#5-creating-automations)
6. [Sending Campaigns](#6-sending-campaigns)

---

## 1. Getting Started
Welcome to the WhatsApp Business API Platform. This guide will help you navigate the features available to your role.

- **Login**: Access the dashboard via your provided email and password.
- **Dashboard**: The main hub showing an overview of your messaging limits, quality rating, and recent activity.

## 2. Connecting WhatsApp
*Role Required: Admin*

1. Navigate to **System > Connect Account**.
2. Click the **Connect with Facebook** button.
3. Follow the popup instructions to log in to your Facebook account and select your WhatsApp Business Account (WABA).
4. Once authorized, the system will automatically sync your Phone Number ID and WABA ID.
5. **Verify**: Check for the "Connected" status badge in the top right.

## 3. Managing Contacts
*Role Required: Manager, Admin*

- **Importing**: Go to **Subscriber Manager**. You can upload a CSV file of contacts or add them manually.
- **Tags**: Use tags to segment users (e.g., "VIP", "Lead", "Newsletter"). Click a contact to edit their tags.
- **Consent**: Ensure you only message users who have opted in. The system tracks "Opt-in" status automatically via the Compliance module.

## 4. Using the Shared Inbox
*Role Required: Agent, Manager, Admin*

1. Go to **Shared Inbox** in the sidebar.
2. **Left Panel**: Shows a list of active conversations. Unread messages are highlighted.
3. **Center Panel**: The chat interface. Type your message and hit Enter or click Send.
4. **Media**: Click the attachment icon to send images or documents.
5. **Templates**: To initiate a conversation after the 24-hour window, you must use a Template Message. Click the "Template" icon to select one.

## 5. Creating Automations
*Role Required: Manager, Admin*

1. Navigate to **Bot Manager** and click **Create New**.
2. **Trigger**: Define a keyword (e.g., "Hi", "Menu") that starts the bot.
3. **Builder Canvas**:
    - **Add Node**: Select a node type (Text, Image, Question, etc.) from the palette.
    - **Connect**: Drag a line from one node's output to another node's input.
    - **Edit**: Click a node to change its content (text message, image URL, etc.).
4. **Save**: Click the "Save" button at the top right. The system will validate your flow.

## 6. Sending Campaigns
*Role Required: Manager, Admin*

1. Go to **Broadcasting > Create Campaign**.
2. **Select Template**: Choose a pre-approved WhatsApp template.
3. **Select Audience**: Choose a Tag or Segment to send to.
4. **Schedule**: Choose "Send Now" or pick a future date/time.
5. **Launch**: Click "Send". You can track delivery stats in the **Broadcasting** list view.
