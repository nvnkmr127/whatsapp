# Knowledge Base Documentation

## What is it?
The **Knowledge Base** is the "Brain" of your AI. It allows you to upload text, PDF files, or website URLs that the AI will read and learn from to answer customer questions accurately.

## Why is it useful?
- **Accuracy**: The AI answers based on *your* data, not generic internet knowledge.
- **24/7 Support**: Your AI agent can answer "What is your return policy?" instantly because you uploaded the policy document here.
- **Easy Training**: Just upload a PDF or paste a link. No coding required.

## Option Buttons (UI Guide)
- **Add Source**:
  - **Upload File**: Upload a PDF or TXT file.
  - **Add URL**: Link to a specific webpage to scrape text from.
  - **Raw Text**: Paste a paragraph of instructions directly.
- **Reprocess**: Forces the AI to read the document again (useful if you updated a file).
- **Preview**: See exactly what text the AI has extracted from your file.
- **Delete**: Remove a document from the AI's memory.

## Use Cases
1.  **Product Manuals**: You upload the PDF user manual for your product. Now the AI can answer, "How do I reset my device?" by virtually reading the manual.
2.  **Pricing Page**: You add your pricing page URL. When prices change on the site, you click **Reprocess**, and the AI learns the new prices.

## Logic Flow
| Feature | Actor | Trigger | Flow | Business Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **Training** | Admin | Upload File | 1. System extracts text from PDF.<br>2. Chunks text into digestible pieces.<br>3. Stores vector embeddings in database.<br>4. AI can now "Search" this memory. | AI becomes a domain expert in your business. |

## How to Use
1.  **Navigate**: Go to **Intelligence** > **Knowledge Base** > **Documentation**.
2.  **Add**: Click **Add Source** -> **Upload File**.
3.  **Upload**: Select your "Policy.pdf".
4.  **Wait**: The status will change to "Indexed".
5.  **Verify**: Click the **Preview** (Eye icon) to check the text was read correctly.
