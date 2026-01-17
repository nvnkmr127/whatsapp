# Products Manager

## What is it?
The **Products Manager** helps you build your "WhatsApp Catalog". Users see these items when they click the "Store" icon in your WhatsApp profile.

## Why is it useful?
- **Native Experience**: Customers can browse and shop without leaving the app.
- **Sync**: Automatically syncs your items to Facebook/Meta Catalog.
- **Rich Details**: Add images, descriptions, prices, and web links for every item.

## Option Buttons (UI Guide)
- **Create Product**: Opens the "Add New Product" form.
- **Sync with Meta**: Pushes your changes to WhatsApp immediately.
- **Search**: Find a product by name or SKU.
- **Action Icons**:
  - **Edit (Pencil)**: Update price or description.
  - **Delete (Trash)**: Remove item from store.

## Use Cases
1.  **New Arrival**: You received a new stock of "Summer T-Shirts". You add them here with photos and prices so customers can order them immediately.
2.  **Price Drop**: You run a sale. You edit the product price here, click **Sync**, and the new price appears on everyone's phone.

## Logic Flow
| Feature | Actor | Trigger | Flow | Business Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **Catalog Sync** | Admin | Click "Sync" | 1. System formats product data.<br>2. Sends API request to Meta Commerce Manager.<br>3. Meta updates the catalog visible in WhatsApp. | Accurate product info for customers. |

## How to Use
1.  **Navigate**: Go to **Commerce** > **Products**.
2.  **Add**: Click **Create Product**.
3.  **Fill Details**:
    - **Name**: e.g., "Blue Denim Jacket".
    - **Retailer ID**: Unique SKU (e.g., `SKU-101`).
    - **Price**: e.g., `50.00`.
    - **Image URL**: Link to the product photo.
4.  **Save**: Click **Save**.
5.  **Publish**: Click the **Sync icon** on the product row to push it to WhatsApp.
