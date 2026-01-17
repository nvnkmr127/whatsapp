# Visual Bot Builder

## What is it?
The **Visual Bot Builder** is a drag-and-drop canvas where you design the logic of your chat automation. You connect "Nodes" (steps) with "Edges" (lines) to create a conversation flow.

## Why is it useful?
- **No Code**: Build complex logic without writing a single line of programming.
- **Visual Clarity**: See exactly how the conversation will flow from step A to step B.
- **Advanced Features**: Drag in nodes for **OpenAI** (ChatGPT), **API Requests**, **Database Layouts**, and more.

## Option Buttons (UI Guide)
- **Toolbar (Left/Top)**: Contains draggable nodes (Text, Image, Question, Conditions).
- **Canvas (Center)**: The workspace where you arrange nodes.
- **Properties Panel (Right)**: When you click a node, this panel shows settings (e.g., text content, image URL).
- **Save Button**: Commits your changes to the live bot.
- **Debug Mode**: Logs internal actions for troubleshooting errors.

## Node Types
1.  **Trigger**: The starting point. Define keywords (e.g., "Hi", "Price") here.
2.  **Message**: Send text, images, or files.
3.  **User Input**: Ask a question and save the answer (e.g., "What is your email?") to a variable.
4.  **Condition**: Logic check (e.g., If `email` contains `@`, go to Path A, else Path B).
5.  **OpenAI**: Send the user's text to ChatGPT and reply with the AI's answer.
6.  **Interactive**: Send buttons or list menus.

## Use Cases
1.  **Customer Support AI**:
    - **Trigger**: "Support".
    - **Node 1**: "Hi! describe your issue."
    - **Node 2 (OpenAI)**: "Analyze input and suggest solution."
    - **Node 3**: Reply to user.
2.  **Lead Gen Form**:
    - **Trigger**: "Quote".
    - **Input 1**: "Name?"
    - **Input 2**: "Budget?"
    - **Condition**: If Budget > 1000 -> Notify Sales Team.

## Logic Flow
| Feature | Actor | Trigger | Flow | Business Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **Logic Execution** | System | User hits Node | 1. System executes node action (e.g., Send Text).<br>2. Finds connected "Edge" to next node.<br>3. Proceeds to next step.<br>4. Loops until end or user input wait. | Seamless, automated conversation handling complex tasks. |

## How to Use
1.  **Navigate**: From Automations List, click **Edit** or **New Automation**.
2.  **Start**: Click the **Start Node** to set your Keywords (e.g., `START`).
3.  **Add Step**: Click **Add Node** (or drag from sidebar) to add a **Text Message**.
4.  **Connect**: Drag a line from the dot on the Start Node to the dot on the Text Node.
5.  **Edit**: Click the Text Node, type "Hello World" in the side panel.
6.  **Save**: Click the **Save** button top right.
