# Contacts Manager

## What is it?
The **Contacts Manager** is your CRM (Customer Relationship Management) database. It stores everyone who has ever messaged you or whom you have added manually.

## Why is it useful?
- **Organization**: Keep all customer data in one searchable place.
- **Segmentation**: Group users using **Tags** (e.g., "VIP", "Lead", "Customer") for targeted marketing.
- **Data Attributes**: Store custom info like "Birthday", "City", or "Order ID".
- **Export/Import**: Bulk upload contacts from Excel or download them for reporting.

## Option Buttons (UI Guide)
- **Import Contacts**: Button to upload a `.csv` or `.xlsx` file of phone numbers.
- **New Contact**: Manually add a single person.
- **Filter**: Search by name, phone number, or tag.
- **Edit Contact**: Click a name to update their tags or attributes.
- **Delete**: Remove a contact (Warning: deletes chat history too).

## Use Cases
1.  **Lead Qualification**: A user chats in. Your agent tags them as "High Potential". Later, you filter for "High Potential" to follow up.
2.  **Migration**: You move from another software. You select **Import Contacts**, upload your CSV, and instantly have your database ready.

## Logic Flow
| Feature | Actor | Trigger | Flow | Business Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **Tagging** | Agent | Profile Update | 1. Agent talks to customer.<br>2. Recognizes customer is interested in "Shoes".<br>3. Adds "Interest: Shoes" tag.<br>4. Profile saved. | Future marketing can target "Shoes" interest specifically. |

## How to Use
1.  **Navigate**: Go to **Engagement** > **Contacts**.
2.  **Add**: Click **New Contact**. Enter Name and Phone Number (with country code).
3.  **Import**: Click **Import**, download the sample template, fill it, and upload your list.
4.  **Manage Tags**: Click on any user row. In the side panel, type a new tag name and hit Enter to assign it.
