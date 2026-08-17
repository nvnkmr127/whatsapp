# Product Catalog Manager

## 1. What is this page?
The Product Catalog Manager is the inventory configuration page of the platform. Located at `/commerce/products`, it allows administrators and marketing managers to create, edit, lock, delete, and synchronize catalog items directly with Meta's Commerce Catalog APIs.

## 2. Why is this page useful?
WhatsApp Catalog messages and transactional templates require product inventory details to be synced and validated with Meta's systems.
- **Why do users need it?** To create and manage their product inventories in one central location, verify stock quantities, and push items directly to WhatsApp's mobile catalog view.
- **What work does it make easier?** It automates the complex Meta API catalog registration process, maps items into categories, handles image uploads, and lets managers lock custom values so automated store syncs don't overwrite them.
- **What business process does it support?** Inventory Syncing, Digital Asset Management, and Direct Chat Sales.
- **What happens without it?** Businesses cannot display interactive product selectors inside WhatsApp chats, meaning customers cannot browse store items or check prices during conversations.

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Admin | To trigger bulk catalog synchronizations with Meta and troubleshoot API sync failures. |
| Marketing Manager | To create new products, upload images, define SKUs, write product descriptions, and organize categories. |

## 4. What can users do here?
- **Manage Products (CRUD):**
  - Search products by Name or Retailer SKU.
  - Create new products by entering Name, Description, Price, Currency, Retailer ID (SKU), Image URL, Page URL, Category, Stock limits, and active states.
  - Upload catalog photos (up to 2MB) directly from their computer, saving them to public server storage.
  - Edit or delete products.
- **Synchronize Catalogs:**
  - **Single Product Sync:** Push a specific product to Meta's Catalog manager.
  - **Bulk Sync ("Sync All"):** Launch a background queue job (`SyncProductsToMetaJob`) to process and upload the team's entire product database to Meta in one click.
- **Categories Filter:** Navigate items by categories (e.g. Products, All).

## 5. What is involved?
- **Product Model:** Core model mapping name, description, SKU (`retailer_id`), price, currency, availability, and active status.
- **Category Model:** Groups products.
- **MetaCommerceService & WhatsAppCommerceService:** Service engines executing Meta API requests.
- **SyncProductsToMetaJob:** The background worker handling batch uploads.

## 6. How does it work?
1. The Marketer opens the Product Manager (`/commerce/products`) and clicks "Create Product".
2. They fill in details: "Gourmet Coffee Beans", price 15, currency USD, and SKU `CF-BN-01`.
3. They upload a photo of the bag, write a description, and select the category "Coffee".
4. Once saved, they click the "Sync" button on the product card.
5. The page calls the backend sync handler. It detects if there is an active Meta Commerce integration (using `MetaCommerceService`) or falls back to standard WhatsApp credentials (`WhatsAppCommerceService`).
6. The service sends a POST request to Meta. Once approved, the coffee beans appear in the business's WhatsApp catalog, enabling automated bots to show the beans in chat flows.

## 7. What happens behind the scenes?
- **Sync State Tracking:** The database uses a `sync_state` column (e.g. `local`, `synced`, `error`). When a product is created or edited, it is marked as `local`. After a successful sync call to Meta, the status changes to `synced`. If Meta rejects the item, the status shows `error` along with the API error description.
- **Field Locking:** When importing catalogs from WooCommerce, the system might overwrite manual updates made inside the platform. To prevent this, users can lock specific attributes. The system registers locked fields in the database, telling WooCommerce webhook routines to skip updates for those locked columns.
- **Resumable Batch Syncur:** The bulk sync button pushes the sync request to the background queue using a database job. This ensures that large catalogs (thousands of products) are chunked and uploaded asynchronously without causing web timeout errors.

## 8. Business Use Cases

**Use Case 1: Pushing New Items to Chatbots**
- **Situation:** A fashion boutique has just received a new winter coat line and wants to recommend it via WhatsApp campaigns.
- **How the feature is used:** They create the product cards in the Product Manager and sync them to Meta. Once synced, they add the SKU codes to their Campaign catalog templates.
- **Customer experience:** Customers receive message alerts and can tap the coat card to view the sizing chart and checkout.
- **Business outcome:** Rapid promotion of new arrivals.

**Use Case 2: Out of Stock Auto-Updates**
- **Situation:** A retail shop sells out of a popular item in their warehouse and wants to prevent customers from buying it on WhatsApp.
- **How the feature is used:** The manager opens the product, changes the availability toggle to "out of stock" or toggles off the "active" switch, and syncs the changes.
- **Customer experience:** Customers see the item marked as out of stock inside their WhatsApp catalog browser.
- **Business outcome:** Prevents customer disappointment and booking conflicts.

**Use Case 3: Overwriting Import Mismatches**
- **Situation:** A product description imported from a WooCommerce integration looks weird and unformatted on mobile displays.
- **How the feature is used:** The marketer opens the Product Manager, edits the description to format it with clean line breaks and emojis, locks the description field, and saves.
- **Customer experience:** Mobile shoppers see a clean, easy-to-read product description.
- **Business outcome:** High-quality mobile catalog pages.

## 9. Industry Use Cases
- **Retail:** Syncing clothing, electronics, or books to display catalog cards in chat.
- **Fitness:** Listing training memberships and class passes as purchase options.
- **Hospitality:** Displaying menu items and gift vouchers for ordering.

## 10. Real Customer Example
A bakery enters a custom cake card in `/commerce/products`. They set the retailer ID to `cake-01`, price to 50, and upload an image of the cake. They click sync, pushing the cake to Meta. Later, a customer messaging the bakery's WhatsApp asks: "What cakes do you have?" The bot triggers, displaying the Gourmet Cake card from the synced catalog. The customer clicks the card, adds it to their cart, and initiates checkout.

## 11. Customer Journey
Marketer creates product card &rarr; Uploads photo to server &rarr; Single or bulk sync to Meta triggered &rarr; Item approved and cataloged &rarr; Customer views card in WhatsApp.

## 12. Inputs
- Product name and description.
- Unique Retailer ID (SKU).
- Price and Currency code.
- Image files or asset URLs.
- Category targets.
- Availability status and stock quantities.

## 13. Outputs
- Saved product records in database.
- Dispatched Meta catalog payload records.
- Background sync queue jobs.

## 14. Dependencies
- **Product Model:** Database target.
- **Category Model:** Dynamic grouping.
- **MetaCommerceService & WhatsAppCommerceService:** Core APIs.

## 15. Permissions
- **Who can access this page:** Users with `manage-campaigns` permission on plans including `commerce`.
- **Who can view information:** Admins/Marketers/Agents.
- **Who can edit:** Admins/Marketers.
- **Who cannot access it:** Standard guest accounts.

## 16. Important Rules
- Retailer IDs (SKUs) cannot contain spaces or special characters (use hyphens or underscores).
- Images must be in standard web formats (JPEG/PNG) and under 2MB.

## 17. Common Problems
- **Problem:** Single product sync returns "Upload rejected by Meta".
  - **Possible reason:** The product image URL is invalid, or the price formatting is missing required decimal inputs.
  - **What the user should do:** Double-check the product image URL is live, make sure your currency code is supported, and retry the sync.
- **Problem:** WooCommerce updates keep overwriting my customized mobile names.
  - **Possible reason:** The field lock toggle was not turned on for the "name" column.
  - **What the user should do:** Open the product, find the field lock options, check "Name", and click update.

## 18. Simple Explanation for Sales
The Product Manager is your WhatsApp store catalog. It lets you add items, set prices, and sync them with WhatsApp so customers can browse your catalog directly inside their chat window.

## 19. Simple Explanation for Marketing
Manage what products show up in your WhatsApp campaigns. Upload pictures, write mobile-optimized descriptions, and sync the catalog with Meta in one click.

## 20. Simple Explanation for Support
If a customer asks about a price change, check the Product Manager to verify the active cost and catalog details.

## 21. Related Features
- [Commerce Dashboard](./commerce.md)
- [Order Manager](./orders.md)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/commerce/products`
- **Implementation:** `App\Livewire\Commerce\ProductManager`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Livewire/Commerce/ProductManager.php`
  - `app/Models/Product.php`
  - `app/Services/Integrations/MetaCommerceService.php`
  - `app/Services/WhatsAppCommerceService.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
