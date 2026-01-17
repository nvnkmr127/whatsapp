# API Documentation

## What is it?
The **API Documentation** is the instruction manual for developers. It lists every available "Endpoint" (command) you can send to the system, what parameters it requires, and what response to expect.

## Why is it useful?
- **Standardization**: Uses RESTful standards (GET, POST, PUT, DELETE).
- **Clarity**: provides copy-paste code examples (cURL, PHP, Node.js).
- **Error Codes**: Explains what "Error 400" or "Error 429" means.

## Core Endpoints
- `GET /api/v1/contacts`: List all contacts.
- `POST /api/v1/messages/send`: Send a WhatsApp message.
- `GET /api/v1/campaigns`: List past campaigns.

## Use Cases
1.  **Integration**: You hire a freelancer to connect your website form to WhatsApp. You send them the link to this API Doc so they know exactly how to code the connection.

## How to Use
1.  **Navigate**: Go to **Developer** > **API Docs**.
2.  **Read**: Select the resource you need (e.g., "Messages").
3.  **Test**: Use Postman to try the endpoints using your API Key.
