# Knowledge Base Manager (Business Brain)

## 1. What is this page?
The Knowledge Base Manager (Business Brain) page is the information feeding and quality auditing dashboard of the platform. Located at `/knowledge-base`, it allows administrators to upload documentation, crawl web URLs, write custom reference text, and review feedback reports (`/knowledge-base/feedback`) detailing questions the AI was unable to answer (unresolved gaps).

## 2. Why is this page useful?
AI customer assistants need accurate business context to answer inquiries about pricing, return rules, or opening hours.
- **Why do users need it?** To feed company-specific knowledge to the AI engine and track customer conversation failures (gaps) to continuously refine the AI's training data.
- **What work does it make easier?** It automates document extraction (PDFs, TXT files), crawls help centers, parses text, and allows manual text editing.
- **What business process does it support?** AI Training Management, Support Automation Auditing, and Knowledge Gap Resolution.
- **What happens without it?** The AI assistant relies on general knowledge, leading to generic or inaccurate answers when asked about business-specific topics.

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Admin | To upload corporate policy documents, configure URL crawlers, and review security audits. |
| Customer Support Lead | To audit fallback logs, resolve questions the AI couldn't answer, and update text details (like holiday store hours). |

## 4. What can users do here?
- **Manage Knowledge Sources:**
  - **Upload Files:** Upload PDF or TXT files (up to 10MB) to be parsed in the background.
  - **Crawl Websites:** Enter website URLs to crawl and index help center or product documentation pages.
  - **Add Raw Text:** Copy-paste text rules, pricing lists, or instructions (indexed instantly).
- **Audit Active Information:**
  - View all sources with Name, Type (file, url, text), size (character counts), and status.
  - Reprocess Sources: Manually trigger reprocessing for URL or file updates.
  - Preview contents: Open a modal to preview stored text.
  - Edit Content: Modify raw text and uploaded document contents inside the app (note: crawled URL content cannot be edited manually).
  - Delete Sources.
- **Resolve Knowledge Gaps (`/knowledge-base/feedback`):**
  - Track customer search inputs and questions the AI assistant could not answer.
  - Filter gaps by status (pending, resolved, ignored) or search term.
  - **Resolve Gaps:** Add resolution notes explaining the correct answer, helping the team see what information needs to be added next.
  - **Ignore Gaps:** Archive irrelevant user queries.

## 5. What is involved?
- **KnowledgeBaseSource Model:** Stores file paths, metadata, sync dates, and extracted content strings.
- **KnowledgeBaseGap Model:** Logs failed customer questions, status tags, and resolution notes.
- **ProcessKnowledgeBaseSourceJob:** Background queue worker that extracts text from PDFs or scrapes website HTML.

## 6. How does it work?
1. The Support Lead opens `/knowledge-base` and adds a raw text source named `shipping_policy` listing delivery prices. The AI learns it instantly.
2. Under URL import, they add `https://mybrand.com/faq` to crawl their online help page.
3. Later, a customer asks the AI: "Do you ship to Alaska?". The AI scans `shipping_policy` and replies with the correct rate.
4. Another customer asks: "Do you accept cryptocurrency?". The AI doesn't find this in the sources, logs the query, and transfers the chat to a human agent.
5. The Support Lead opens `/knowledge-base/feedback`, sees the pending gap "Do you accept cryptocurrency?", and clicks "Resolve". They write a note: "Added Bitcoin rules to the payment policy raw text source."

## 7. What happens behind the scenes?
- **Background Content Processing:** Uploading files or URLs saves the record with a `pending` status and dispatches `ProcessKnowledgeBaseSourceJob`. The worker scrapes the webpage or parses the PDF, updates the content column with the extracted text, and changes the status to `ready`.
- **Text Safety Filtering:** Raw text entries bypass background jobs and save directly as `ready` since no document extraction or web scraping is needed.
- **Audit Logging:** Creating, updating, or deleting sources triggers system audits (e.g. `knowledge_base.added`), logging who performed the action and what was changed.

## 8. Business Use Cases

**Use Case 1: Crawling Public FAQs**
- **Situation:** A business maintains an extensive, frequently updated FAQ page on their website and wants the AI to stay in sync.
- **How the feature is used:** They link their FAQ URL on this page. When web content updates, they click the "Reprocess" icon to refresh the AI's training data.
- **Customer experience:** Customers get accurate website answers directly in chat.
- **Business outcome:** Reduced FAQ support volume.

**Use Case 2: Resolving Missing Answers**
- **Situation:** A company launches a new product line, and customers start asking about warranty terms that aren't in the knowledge base.
- **How the feature is used:** The Support Lead opens the Feedback dashboard. They identify the new product queries under the "pending" gap filter, add resolution notes, and update their raw text sources.
- **Customer experience:** N/A (Internal training refinement).
- **Business outcome:** Continuous AI improvement.

**Use Case 3: Uploading internal CSV or PDF Price Sheets**
- **Situation:** A wholesale distributor has a complex 50-page PDF price guide.
- **How the feature is used:** They upload the PDF on this page. The system parses and stores the text.
- **Customer experience:** Customers ask for item pricing in chat and receive instant quotes.
- **Business outcome:** Fast sales inquiries handling.

## 9. Industry Use Cases
- **Travel & Hospitality:** Uploading booking policies and cancellations guidelines.
- **E-commerce:** Scraping FAQs, shipping restrictions, and refund rules.
- **SaaS Platforms:** Scraping user guides and release notes.

## 10. Real Customer Example
An electronics brand links their support site to the Business Brain. When customers ask about return windows, the AI answers using crawled FAQ text. When a customer asks if they repair 10-year-old devices, the AI fails to find the answer, logs a "pending" gap in the feedback portal, and passes the chat to an agent. The manager reviews the feedback, logs the answer, and resolves the gap.

## 11. Customer Journey
Upload document / crawl URL / paste text &rarr; Processing job indexes content &rarr; AI answers customer queries using stored data &rarr; Failed questions logged as gaps &rarr; Manager reviews feedback and updates sources.

## 12. Inputs
- Document files (PDF, TXT under 10MB).
- Target URLs.
- Raw text descriptions and titles.
- Resolution notes.

## 13. Outputs
- Saved `KnowledgeBaseSource` data.
- Dispatched text processing jobs.
- Updated `KnowledgeBaseGap` statuses.
- System audit trails.

## 14. Dependencies
- **KnowledgeBaseSource & KnowledgeBaseGap Models:** DB storage.
- **ProcessKnowledgeBaseSourceJob:** Background parser.
- **Storage Disk:** File storage.

## 15. Permissions
- **Who can access this page:** Users with `manage-settings` permission on plans including `ai`.
- **Who can view information:** Support Leads/Admins.
- **Who can edit:** Admins.
- **Who cannot access it:** Standard support agents.

## 16. Important Rules
- PDF and TXT file uploads are limited to a maximum of 10MB.
- Crawled website content cannot be edited directly inside the app; to update crawled text, you must edit the website page and trigger a reprocess run.

## 17. Common Problems
- **Problem:** Processing status is stuck on "Pending" for a file upload.
  - **Possible reason:** The background queue worker is down, or the file contains unreadable formats.
  - **What the user should do:** Click the "Reprocess" icon to queue the file again, or contact your admin to verify the queue processor status.
- **Problem:** URL import returns an error.
  - **Possible reason:** The target website is behind a firewall, blocks scrapers, or requires logins.
  - **What the user should do:** Copy-paste the webpage content into a "Raw Text" source instead.

## 18. Simple Explanation for Sales
The Business Brain page is where you train your AI. You can upload help files, paste rules, or link your website, and the AI will use this information to answer customer questions automatically.

## 19. Simple Explanation for Marketing
Train your AI. Link your product pages, upload FAQs, and review the feedback dashboard to see what questions your AI couldn't answer, helping you optimize campaign content.

## 20. Simple Explanation for Support
If the AI cannot answer a customer's question, it gets flagged in the Feedback portal on this page. Support leads can review these questions, add the answers to the knowledge base, and mark them as resolved.

## 21. Related Features
- [AI Assistant Settings](./commerce-settings.md)
- [Agent Workspace](./chat-dashboard.md)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/knowledge-base` & `/knowledge-base/feedback`
- **Implementation:** `App\Livewire\Developer\KnowledgeBaseManager` & `App\Livewire\Developer\KnowledgeBaseFeedback`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Livewire/Developer/KnowledgeBaseManager.php`
  - `app/Livewire/Developer/KnowledgeBaseFeedback.php`
  - `resources/views/livewire/developer/knowledge-base-manager.blade.php`
  - `resources/views/livewire/developer/knowledge-base-feedback.blade.php`
  - `app/Models/KnowledgeBaseSource.php`
  - `app/Models/KnowledgeBaseGap.php`
  - `app/Jobs/ProcessKnowledgeBaseSourceJob.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
