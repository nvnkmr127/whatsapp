# AI Configuration

## What is it?
**AI Configuration** is where you define the personality and behavior of your AI Agent. You choose the model (e.g., GPT-4), set the creativity level, and give it specific instructions on how to act.

## Why is it useful?
- **Personality**: Make your AI sound professional, friendly, or sales-focused.
- **Guardrails**: Define "Stop Keywords" or topics the AI should refuse to discuss.
- **Integration**: Connect your own OpenAI API key for full control.

## Option Buttons (UI Guide)
- **API Key**: Enter your OpenAI Key here.
- **Model**: Select `gpt-4o` (Smartest) or `gpt-3.5-turbo` (Faster/Cheaper).
- **Persona**:
  - **Support**: "You are a helpful support agent."
  - **Sales**: "You are a persuasive sales consultant."
  - **Custom**: Write your own prompt (e.g., "You are a pirate").
- **Creativity (Temperature)**:
  - **Low (Strict)**: Sticks to facts (Good for support).
  - **High (Creative)**: More chatty and inventive (Good for marketing).
- **Header/Footer**: Automatically add text to every AI message (e.g., "AI-generated response").

## Use Cases
1.  **Sales Bot**: You select the "Sales" persona and High creativity. The bot proactively suggests upgrades and uses enthusiastic language.
2.  **Strict Support**: You select "Support" and Low creativity. The bot gives short, factual answers from the Knowledge Base and never invents information.

## Logic Flow
| Feature | Actor | Trigger | Flow | Business Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **Persona Check** | System | Message Generation | 1. System appends "System Prompt" (Persona) to chat context.<br>2. AI reads "You are a support agent..."<br>3. AI generates response aligned with that identity. | Consistent brand voice and safe interaction. |

## How to Use
1.  **Navigate**: Go to **Intelligence** > **Knowledge Base** > **AI Config**.
2.  **Setup**: Enter your OpenAI API Key and click **Test Connection**.
3.  **Define**: Choose a **Persona** (e.g., Support) and set **Creativity** to Normal (0.7).
4.  **Refine**: Add a **Footer Message** like "Powered by AI" for transparency.
5.  **Save**: Click **Update Settings**.
