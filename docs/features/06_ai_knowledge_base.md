# AI Knowledge Base

## What is it?
The **AI Knowledge Base** is the "brain" of your automation. Instead of manually writing thousands of "If/Then" rules, you simply upload your existing business documents (PDFs, Word docs, Text files), and the AI reads them. It then uses that information to answer customer questions intelligently and naturally.

## Why is it useful?
- **Zero Setup**: No need to build complex flowcharts for FAQs. Just upload your policy document.
- **Natural Language**: Understands human speech (e.g., "Can I get my money back?" vs "What is the return policy?" are treated the same).
- **Consistent Answers**: Ensures every agent and bot gives the exact same correct information based on your official docs.
- **Fallback**: Takes over when your structured bots don't know the answer.

## Option Buttons (UI Guide)
- **Upload Document**: Button to select files from your computer (PDF, TXT, DOCX).
- **Train Bot**: Process the uploaded documents so the AI can "learn" them.
- **Test Sandbox**: A chat window where you can ask your bot questions to verify its answers before going live.
- **Settings**:
  - **Personality**: Set the tone (e.g., "Professional", "Friendly", "Concise").
  - **Confidence Threshold**: How sure the AI must be before answering (default 70%).

## Use Cases
1.  **Policy Queries**: Upload your 50-page Employee Handbook. Staff can ask, "How many notice days for resignation?" and get an instant answer.
2.  **Product Support**: Upload the User Manual for a vacuum cleaner. Customers can ask, "Why is the red light blinking?" and the AI explains the error code.
3.  **Pricing Info**: Upload your Price List. The bot can answer specific questions like "How much is the Pro Plan for 5 users?".

## Logic Flow
| Feature | Actor | Trigger | Flow | Business Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **AI Reply** | System (AI) | User asks complex question | 1. User asks "Do you ship to Alaska?"<br>2. System checks Knowledge Base.<br>3. AI finds "Shipping Policy" doc.<br>4. AI formulates answer: "Yes, we ship to Alaska for a $15 fee."<br>5. Sends reply. | Accurate, instant support without human involvement. |

## How to Use
1.  **Navigate**: Go to **AI Settings** > **Knowledge Base**.
2.  **Add Content**: Click **Upload Source**. Select your PDF FAQ or Policy document.
3.  **Train**: Click **Sync/Train**. Wait a few moments for the status to change to "Active".
4.  **Test**: Use the **Test Bot** window on the right. Ask a question that is answered in your document.
5.  **Enable**: Ensure the AI Bot is switched **ON** in the main settings.
