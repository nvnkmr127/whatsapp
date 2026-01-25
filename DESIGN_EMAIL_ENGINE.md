# System-Only Email Template Engine Design

## Overview
This system allows the management of critical system emails (OTP, Alerts) via database templates while enforcing strict schema and locking rules to prevent breaking changes.

## Components

### 1. Database Schema (`email_templates` table)
- **`slug`**: Unique identifier (e.g., `user-otp-login`). Locked for system templates.
- **`variable_schema`**: JSON array defining strict variables (e.g., `['code', 'name']`).
- **`is_locked`**: Boolean. If `true`, prevents deletion and structural changes (slug, schema).
- **`content_html` / `content_text`**: The actual template content.

### 2. Models & Enums
- **`EmailTemplate`**: Eloquent model with casts for `EmailUseCase` enum.
- **`EmailUseCase`**: Existing Enum (`OTP`, `ALERT`, `MARKETING`).

### 3. Service Layer (`EmailTemplateService`)
- **`render(slug, data)`**: Fetches template, validates data against schema, and renders content.
- **`validateData(template, data)`**: Ensures strict adherence to the schema.
- **`validateTemplateContent(content, schema)`**: Ensures the admin doesn't add invalid placeholders to the template.

### 4. Admin Management (`EmailTemplateController`)
- **`update`**: Allows editing subject/content.
  - **Logic**: If `is_locked`, strictly forbids changing `slug` or `variable_schema`. Validates that new content only uses allowed variables.
- **`preview`**: Generates a preview with dummy data derived from the schema.

### 5. Deployment (`EmailTemplateSeeder`)
- Seeds initial system templates (`user-otp-login`, `system-health-alert`).
- Sets `is_locked = true` for these critical templates.

## Usage

```php
use App\Services\Email\EmailTemplateService;
use App\Mail\DynamicSystemMail;
use Illuminate\Support\Facades\Mail;

// 1. Render content
$service = app(EmailTemplateService::class);
$rendered = $service->render('user-otp-login', [
    'name' => $user->name,
    'code' => $otpCode,
    'expiry' => '10 minutes'
]);

// 2. Send Email
Mail::to($user->email)->send(new DynamicSystemMail(
    $rendered['subject'], 
    $rendered['html'], 
    $rendered['text']
));
```
