# Contact Management (CRM)

## What is it?
The **Contact Manager** is your built-in CRM (Customer Relationship Management) system. It stores every user who has ever engaged with your business on WhatsApp, along with their details like name, phone number, email, and custom attributes.

## Why is it useful?
- **Organization**: Keep your customer data clean and accessible in one place.
- **Segmentation**: Use "Tags" to group customers (e.g., "VIP", "New Lead", "Churned") for targeted marketing.
- **Custom Data**: Store specific info like "Birthday", "Last Order ID", or "Subscription Status".
- **Bulk Actions**: Import thousands of contacts via CSV or export them anytime.

## Option Buttons (UI Guide)
- **Import Contacts**: Button to upload a `.csv` file of numbers.
- **Add Contact**: Manually create a single new profile.
- **Search Bar**: Find a contact by Name or Phone Number.
- **Filter Menu**:
  - **By Tag**: Show only users with specific tags.
  - **By Status**: Show "Subscribed" vs "Unsubscribed".
- **Contact Profile (Click on a Name)**:
  - **Edit Details**: Update name, email, etc.
  - **Manage Tags**: Add or remove tags.
  - **Custom Attributes**: View or set special fields (e.g., `City: New York`).

## Use Cases
1.  **Lead Scoring**: You tag anyone who asks about "Pricing" as a "Hot Lead" so your sales team allows focuses on them first.
2.  **Newsletter Segmentation**: You separate contacts into "English Speakers" and "Spanish Speakers" to send the right campaign language.
3.  **Blacklisting**: If a user is abusive, you block them or mark them "Do Not Contact" to prevent future messages.

## Logic Flow
| Feature | Actor | Trigger | Flow | Business Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **Tagging** | Manager | User completes a purchase | 1. Manager searches for user "John Doe".<br>2. Opens profile.<br>3. Adds tag "Customer".<br>4. Removes tag "Lead". | Accurate database; Better targeting for future upsells. |

## How to Use
1.  **Access CRM**: Click **Contacts** in the sidebar.
2.  **Import (Optional)**: Click **Import**, download the sample CSV, fill it with your numbers, and upload it back.
3.  **Organize**:
    - Click on a contact's row.
    - Click **+ Add Tag**.
    - Type a new tag (e.g., "October Event") and hit Enter.
4.  **Edit Info**: Click the **Edit** pencil icon to update their Name or Email manually.
