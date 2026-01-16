# Changelog

## [1.1.0] - 2026-01-16

### Added
- **Billing & Subscription**: Complete plan management system for Super Admins and billing dashboard for tenants.
- **Commerce Engine**: Integrated product catalog, order management, and automated WhatsApp notifications for order updates.
- **Analytics Dashboard**: Comprehensive messaging statistics and real-time customer event tracking.
- **Developer Portal**: Dedicated space for managing webhooks, API tokens, and technical documentation.
- **AI Knowledge Base**: "Business Brain" module for training bots on custom documentation and categories.
- **Activity Logs**: System-wide audit logging to track administrative and agent actions.

### Changed
- **UI/UX Refinement**: Redesigned Sidebar, Header, and core modules (Contacts, Settings) for a premium, unified aesthetic.
- **Navigation**: Restructured sidebar to include new Billing, Commerce, and Developer sections.

### Fixed
- **Blade Syntax**: Resolved various `@endif` and type mismatch errors in Livewire components.
- **Database Schema**: Fixed missing `team_id` columns and table name inconsistencies in analytics and compliance modules.

---

## [1.0.0] - 2026-01-13 (Production Ready)

### Added
- **Validation**: Server-side validation for the Automation Builder to prevent saving empty or invalid flows.
- **Error Handling**: Enhanced `try-catch` blocks in critical save operations to ensure data integrity and user feedback.
- **Feedback**: Session-based flash messages for success and error states in the Automation Builder.

### Changed
- **Cleanup**: Removed `console.log` and `console.error` debugging artifacts from the WhatsApp Configuration backend and frontend views.
- **Security**: Hardened webhook processing to ensure secure formatting and logging.

### Fixed
- **UI/UX**: Resolved minor visual glitches in the "Connect with Facebook" flow.

---

## [0.9.0] - 2026-01-10 (Beta)

### Added
- **Visual Automation Builder**: Initial release of the drag-and-drop bot builder.
- **Shared Inbox**: Real-time chat interface for agents.
- **Campaigns**: Basic broadcasting capabilities.
- **WhatsApp Integration**: Embedded signup flow implementation.

### Fixed
- **Sidebar**: Fixed navigation structure and missing icons.
- **Webhooks**: Resolved verify token mismatch issues during initial setup.
