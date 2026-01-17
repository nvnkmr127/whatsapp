# Ecommerce Integrations

## What is it?
**Ecommerce Integrations** allows you to connect your external online store (Shopify or WooCommerce) to the WhatsApp platform. This connection automatically imports your products and keeps them in sync.

## Why is it useful?
- **Automatic Sync**: No need to manually type product names and prices again. If you update Shopify, WhatsApp updates too.
- **Order Pushing**: (Future) When a WhatsApp order is placed, it can be pushed back to your Shopify dashboard.

## Option Buttons (UI Guide)
- **Connect New**: Opens the selection popup.
- **Platform Icons**:
  - **Shopify**: Connect using your store domain and Access Token.
  - **WooCommerce**: Connect using your site URL and Consumer Key/Secret.
- **Sync Now**: Manually triggers an immediate pull of all products.
- **Delete**: Removes the connection and stops syncing.

## Use Cases
1.  **Migration**: You already have a successful WooCommerce site. You want to sell on WhatsApp. You connect the integration, click **Sync**, and within minutes your 500 products are listed on your WhatsApp Business Profile.

## Logic Flow
| Feature | Actor | Trigger | Flow | Business Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **Product Import** | Admin | Click "Sync Now" | 1. System calls Shopify/WooCommerce API.<br>2. Fetches product list.<br>3. Creates/Updates local Product records.<br>4. Notifies user "500 Products Synced". | Accurate inventory across channels. |

## How to Use
1.  **Navigate**: Go to **Commerce** > **Integrations**.
2.  **Add**: Click **Connect New**.
3.  **Select**: Choose **Shopify**.
4.  **Credentials**:
    - **Domain**: `mystore.myshopify.com`
    - **Access Token**: Paste the token from your Shopify Admin.
5.  **Connect**: Click **Save**.
6.  **Sync**: Click the **Sync** button on the new card to fetch your items.
