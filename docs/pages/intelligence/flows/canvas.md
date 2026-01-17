# Visual Canvas

## What is it?
The **Visual Canvas** is where you design the layout of your WhatsApp Flow screens. It allows you to add components like Text Inputs, Dropdowns, Date Pickers, and Checkboxes.

## Why is it useful?
- **Drag & Drop**: Build complex forms without coding JSON.
- **Screen Management**: Create multi-step forms (Screen 1 -> Next -> Screen 2).
- **Validation**: Set fields as "Required" or define input types (Email, Number) easily.

## Option Buttons (UI Guide)
- **Toolbox (Left)**: List of available components (Text, Radio, Checkbox, Footer).
- **Screen Manager**: Tabs to switch between "Welcome Screen", "Form Screen", "Success Screen".
- **Properties (Right)**: Edit the label, placeholder, and variable name for the selected component.
- **Deploy**: Publishes the flow design to Meta so it goes live.

## Use Cases
1.  **Survey**:
    - **Screen 1**: "How was your experience?" (Radio Buttons: Good/Bad).
    - **Screen 2**: "Any comments?" (Text Area).
    - **Footer**: "Submit Feedback".

## Logic Flow
| Feature | Actor | Trigger | Flow | Business Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **Design Publish** | Admin | Click "Deploy" | 1. System validates form structure.<br>2. Converts visual design to Meta JSON format.<br>3. Pushes to WhatsApp API.<br>4. Flow becomes ready to send. | Rapid iteration of customer-facing forms. |

## How to Use
1.  **Open Canvas**: Create a new flow or edit an existing one.
2.  **Add Components**: Click **Text Input** to add a name field.
3.  **Configure**: Click the field on the canvas. On the right, set **Label** to "Your Name" and **Key** to `name`.
4.  **Add Screen**: Click **+ Screen** to add a second page.
5.  **Deploy**: Click the **Deploy** button to save and publish.
