# Automation Builder (Bot Manager)

## What is it?
The **Automation Builder** is a visual, drag-and-drop tool used to create intelligent chatbots. You can design "Flows" that guide a customer through a conversation automatically—answering questions, collecting data, or providing menus—without any human intervention.

## Why is it useful?
- **24/7 Availability**: Bots never sleep. They reply instantly at 3 AM.
- **Scalability**: Handle 1,000 customers simultaneously with zero wait time.
- **Lead Qualification**: Ask questions (Name, Budget, Interest) to qualify leads before passing them to a human.
- **Cost Reduction**: Automate repetitive queries to save agent time for complex issues.

## Option Buttons (UI Guide)
- **Create New Flow**: Opens the blank canvas.
- **Trigger**: The starting point (e.g., User types "Hi" or clicks "Get Started").
- **Node Palette (Sidebar)**:
  - **Text Node**: Send a simple text message.
  - **Image/Video Node**: Send media.
  - **Buttons/List**: Offer clickable choices (e.g., "Support", "Sales", "Website").
  - **User Input**: Wait for the user to type something (e.g., "What is your email?").
  - **Condition**: Logic branching (If X, then go here; else go there).
- **Save Flow**: Compiles and activates the bot.

## Use Cases
1.  **Welcome Menu**: User sends "Hi" -> Bot replies "Welcome to [Brand]. Choose an option: 1. Shop 2. Support".
2.  **Lead Gen**: User asks for pricing -> Bot asks "What is your email?" -> Bot sends PDF -> Bot notifies Sales Team.
3.  **FAQ Handling**: User asks "Refund policy" -> Bot detects keyword "Refund" -> Bot sends the policy text.

## Logic Flow
| Feature | Actor | Trigger | Flow | Business Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **Chatbot** | System (Auto) | User messages "Hi" | 1. Bot detects start keyword.<br>2. Bot sends Welcome Menu with 3 buttons.<br>3. User clicks "Support".<br>4. Bot replies with Support Options. | Immediate response; Zero waiting time for customer; Agent workload reduced. |

## How to Use
1.  **Open Builder**: Go to **Bot Manager** > **Create New**.
2.  **Set Trigger**: Enter the keyword(s) that will start this bot (e.g., "Menu", "Start").
3.  **Drag & Drop**:
    - Drag a **Text Node** onto the canvas. Type your welcome message.
    - Drag a **Button Node**. Connect the Text Node to the Button Node.
    - Add buttons like "Pricing" and "Contact Us".
4.  **Connect**: Draw lines between the nodes to define the path the conversation should follow.
5.  **Test**: Click **Save**. Open WhatsApp and send the trigger word to your business number to see it in action.
