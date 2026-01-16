# WhatsApp Business API Management Platform

A comprehensive SaaS solution for managing WhatsApp Business API interactions, featuring a multi-tenant architecture, shared inbox, automation builder, and commerce integration.

## Features

- **Multi-Tenant Architecture**: Robust team and user management via Laravel Jetstream.
- **Shared Team Inbox**: Real-time customer support with media support and message threading.
- **Automation Builder**: Visual drag-and-drop bot builder for automated message flows.
- **Broadcast Campaigns**: Bulk messaging with scheduling and audience segmentation.
- **E-Commerce Integration**: Shop-by-chat experience with AI-powered assistant.
- **Consent & Compliance**: Immutable logs for user opt-in/opt-out status.
- **Activity Auditing**: Detailed logging of sensitive actions for security compliance.

## Technical Stack

- **Framework**: Laravel 11 / PHP 8.2+
- **Frontend**: Livewire 3 + TailwindCSS + Alpine.js
- **Database**: MySQL / PostgreSQL
- **Real-time**: Laravel Reverb
- **Queue**: Redis

## Getting Started

### Prerequisites

- PHP 8.2+
- Composer
- Node.js & NPM
- MySQL/PostgreSQL
- Redis

### Installation

1. **Clone the repository**:
   ```bash
   git clone <repository-url>
   cd whatsapp-business-api
   ```

2. **Install dependencies**:
   ```bash
   composer install
   npm install
   ```

3. **Environment Setup**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database Configuration**:
   Update `.env` with your database credentials and run migrations:
   ```bash
   php artisan migrate
   php artisan db:seed --class=PlanSeeder
   ```

5. **WhatsApp Configuration**:
   Add your Meta App credentials to `.env`:
   ```env
   WHATSAPP_VERIFY_TOKEN=your_verify_token
   WHATSAPP_APP_SECRET=your_app_secret
   ```

6. **Run the application**:
   ```bash
   npm run dev
   php artisan serve
   ```

## Development & Testing

Run tests to ensure system stability:
```bash
php artisan test
```

## Security

Webhooks are protected via `X-Hub-Signature-256` verification. Sensitive API tokens are encrypted at rest in the database.

## License

Personal/Commercial License - (C) 2026.
