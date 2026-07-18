# Technology Stack

**Analysis Date:** 2026-07-18

## Languages

**Primary:**
- PHP 8.2+ - Backend API and business logic (`app/`, `config/`, `routes/`)
- JavaScript/TypeScript - Frontend build tooling and UI components (`resources/js/`)
- Blade/PHP - Template rendering for views (`resources/views/`)

**Secondary:**
- HTML/CSS - UI markup and styling (`resources/css/`, `resources/views/`)

## Runtime

**Environment:**
- PHP 8.2+ (configured via `composer.json` platform)
- Node.js (for frontend build via Vite)

**Package Manager:**
- Composer ^2.0 (PHP dependencies)
- npm/yarn (Node.js dependencies)
- Lockfile: `composer.lock`, `package-lock.json` (present)

## Frameworks

**Core:**
- Laravel 12.0 - Full-stack web framework (`app/`, `routes/`, `config/`)
- Livewire 3.6.4 - Reactive PHP components for dynamic UI (`app/Livewire/`, `resources/views/livewire/`)
- Laravel Jetstream 5.4 - Authentication and team scaffolding (`app/Actions/`)
- Laravel Reverb 1.6 - WebSocket server for real-time features (`config/reverb.php`)
- Laravel Octane 2.17 - Application server for high performance

**Frontend:**
- Vite 7.0.7 - Module bundler for asset compilation (`package.json`)
- Tailwind CSS 3.4.0 - Utility-first CSS framework (`resources/css/`, `tailwind.config.js`)
- Tailwind CSS Forms 0.5.7 - Form component utilities
- Tailwind CSS Typography 0.5.10 - Typography utilities

**API & Communication:**
- Laravel Sanctum 4.0 - Simple token-based API authentication (`config/sanctum.php`)
- Pusher.js 8.4.0 - WebSocket client for real-time messaging
- Axios 1.11.0 - HTTP client for frontend API calls
- Laravel Echo 2.2.7 - Broadcaster abstraction for WebSocket events

**Testing:**
- PHPUnit 11.5.3 - PHP unit testing framework (`tests/`)
- Mockery 1.6 - Mocking library for tests

**Build/Dev:**
- Laravel Vite Plugin 2.0.0 - Vite plugin for Laravel integration
- PostCSS 8.5.6 - CSS transformation tool
- Autoprefixer 10.4.23 - Vendor prefix automation
- Concurrently 9.0.1 - Run multiple commands in parallel for dev environment

## Key Dependencies

**Critical:**
- `laravel/framework` ^12.0 - Core Laravel framework
- `livewire/livewire` ^3.6.4 - Reactive PHP UI components
- `league/flysystem-aws-s3-v3` ^3.35 - S3/R2 file storage adapter (via Flysystem)
- `predis/predis` ^3.3 - Redis PHP client for caching and queue
- `barryvdh/laravel-dompdf` ^3.1 - PDF generation (used in `app/Jobs/`, document exports)
- `phpoffice/phpspreadsheet` ^5.5 - Excel spreadsheet generation (used in `app/Jobs/`)

**Infrastructure:**
- `google/auth` ^1.45 - Google authentication libraries (Firebase, OAuth)
- `resend/resend-php` ^1.1 - Resend email service SDK (`app/Mail/`)
- `sentry/sentry-laravel` ^4.25 - Error tracking integration (optional, `config/sentry.php`)
- `laravel/socialite` ^5.16 - OAuth provider library for Facebook/Google login
- `smalot/pdfparser` ^2.12 - PDF parsing utility

**Development:**
- `laravel/pint` ^1.24 - PHP linting and formatting
- `laravel/sail` ^1.41 - Docker development environment
- `laravel/pail` ^1.2.2 - Log viewing tool
- `fakerphp/faker` ^1.23 - Fake data generation for tests
- `nunomaduro/collision` ^8.6 - Prettier error display
- `opcodesio/log-viewer` ^3.21 - UI for browsing logs (`config/log-viewer.php`)

## Configuration

**Environment:**
- `.env` file (not tracked) - Runtime secrets and configuration
- `.env.example` - Template for required environment variables
- Configuration files in `config/` directory (25+ config files)
  - `config/services.php` - Third-party service credentials
  - `config/whatsapp.php` - WhatsApp API configuration
  - `config/database.php` - Database connections (SQLite, MySQL, PostgreSQL, MariaDB)
  - `config/mail.php` - Email configuration with multi-mailer support
  - `config/queue.php` - Queue driver configuration
  - `config/broadcasting.php` - WebSocket and real-time configuration
  - `config/filesystems.php` - File storage disk configuration (local, S3, R2)
  - `config/webhook-platforms.php` - Webhook platform mappings (Shopify, Stripe, WooCommerce)

**Build:**
- `vite.config.js` - Vite build configuration
- `tailwind.config.js` - Tailwind CSS configuration
- `postcss.config.js` - PostCSS configuration
- `.eslintrc` - JavaScript linting rules
- `.prettierrc` - Code formatting rules
- `.phpunit.xml` - PHPUnit test configuration

## Platform Requirements

**Development:**
- PHP 8.2+
- Composer (PHP dependency manager)
- Node.js (for frontend build)
- Redis (for cache, queue, real-time features)
- MySQL/PostgreSQL/SQLite (database)
- Git (version control)

**Production:**
- Laravel application running on PHP 8.2+ application server (Octane, FPM, or similar)
- MySQL 8.0+ or PostgreSQL 12+ for data persistence
- Redis server for cache, queue, and broadcasting
- Node.js build environment (for asset compilation, not runtime)
- Web server (Nginx or Apache) with PHP support
- Cloudflare R2 or AWS S3 account (for file storage)

## Deployment & Operations

**Docker Support:**
- Laravel Sail included (`docker-compose.yml` compatible)
- Multi-container setup for development

**Performance Optimization:**
- Laravel Octane for high-throughput request handling
- Redis caching layer
- Database query optimization via Eloquent ORM
- Lazy loading for asset compilation via Vite

**Database Migrations:**
- Located in `database/migrations/`
- Seeders in `database/seeders/`
- Factories in `database/factories/` for test data

---

*Stack analysis: 2026-07-18*
