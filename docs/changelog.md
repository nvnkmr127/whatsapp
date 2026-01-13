# Changelog

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
