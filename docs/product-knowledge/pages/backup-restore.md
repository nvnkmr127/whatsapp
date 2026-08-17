# Backup & Disaster Recovery

## 1. What is this page?
The Backup & Disaster Recovery page is the system security and data archiving dashboard of the platform. Located at `/backups`, it allows administrators on supported plans to generate database snapshots, connect Google Drive cloud synchronization, download secure backup files, and restore workspace data.

## 2. Why is this page useful?
Data corruptions, bad API configurations, or accidental bulk contact deletions can stop customer communications.
- **Why do users need it?** To secure their customer CRM data, automated configurations, and campaign templates against accidental data loss or hardware failures.
- **What work does it make easier?** It offers one-click manual snapshot triggers, pings Google Drive links for automated offsite uploads, and provides guided restoration paths.
- **What business process does it support?** System Archiving, Disaster Recovery Planning, and Regulatory Data Compliance.
- **What happens without it?** Accidental data loss or corruption is permanent, requiring teams to manually re-enter settings and contact databases.

## 3. Who uses this page?
| Role | Why they use it |
|---|---|
| Admin | To trigger manual backups, download zip files for local audits, connect Google Drive sync, and restore previous system snapshots. |

## 4. What can users do here?
- **Trigger Manual Snapshots:** Generate a full database and configuration backup immediately.
- **Google Drive Integration (Cloud Sync):** Connect or disconnect Google Drive accounts to automatically upload and store snapshots offsite.
- **Monitor Retention Metrics:**
  - View Local Retention limits (local server stores snapshots for 7 days).
  - View Cloud Retention limits (unlimited backups on Google Drive).
- **Audit Backup History:**
  - View a paginated history list of recovery points containing dates, types (manual vs auto), and processing statuses.
- **Secure File Downloads:**
  - Download completed backup ZIP packages.
  - *Safety Check:* Platform-managed (Model B) backups are locked; only custom tenant backups can be downloaded.
- **One-Click Atomic Restore (Modal):**
  - Revert the entire workspace database back to a selected snapshot.
  - Enforces typing "RESTORE" to prevent accidental triggers.

## 5. What is involved?
- **TenantBackup Model:** Stores metadata, paths, file sizes, and statuses.
- **Integration Model:** Stores Google Drive credentials.
- **BackupService:** The backend engine that runs zip extraction and SQL restorations.

## 6. How does it work?
1. The Admin goes to `/backups` before making major routing changes.
2. They click "Create Manual Backup". The system builds a database snapshot.
3. They make their modifications. If the new routing rules accidentally erase contact lists, they return to the backups page.
4. They find the snapshot they created, and click "Restore Data".
5. The Critical Restoration modal appears. They type `RESTORE` and click confirm.
6. The `BackupService` replaces the current tables with the snapshot data, restoring the contact list.

## 7. What happens behind the scenes?
- **Cryptographically Signed Download URLs:** When an admin clicks download, the system verifies team ownership and generates a Laravel-signed URL (`URL::temporarySignedRoute()`) that expires in 5 minutes. The link routes to `SecureDownloadController::stream()` to serve the file from storage. This prevents unauthorized direct file URL access.
- **Model B Access Control:** In hybrid multi-tenant environments, backups marked as Model B are platform-managed. To prevent data leakage, the controller blocks downloads for standard team admins on these backups, requiring platform Super Admin clearance.
- **Atomic Restoration Integrity:** During restoration, the `BackupService` logs audit trails, locks write operations, replaces database records with the snapshot file, and writes a success audit trail before releasing the system lock. If restoration fails, it rolls back the changes and logs the errors.

## 8. Business Use Cases

**Use Case 1: Pre-Upgrade System Protection**
- **Situation:** An admin wants to migrate storefront plugins on WooCommerce and edit customer tags but fears data collisions.
- **How the feature is used:** They run a manual backup on this page right before updating settings.
- **Customer experience:** N/A (Internal use).
- **Business outcome:** Safe migrations with an instant fallback path.

**Use Case 2: Setting Up Offsite Archives**
- **Situation:** A business wants to store daily database records on their corporate Google Drive for compliance.
- **How the feature is used:** They link their Google Drive account on the card.
- **Customer experience:** N/A (Internal use).
- **Business outcome:** Automated offsite backups that bypass local 7-day retention limits.

**Use Case 3: Recovery From Malicious Deletions**
- **Situation:** A disgruntled employee deletes customer lists and active campaigns.
- **How the feature is used:** The owner opens the backup log, selects a clean yesterday snapshot, types `RESTORE`, and runs the recovery.
- **Customer experience:** Normal operations resume.
- **Business outcome:** Complete recovery from malicious actions.

## 9. Industry Use Cases
- **Retail:** Securing product catalog templates.
- **Education:** Storing student intake logs.
- **Finance:** Retaining message histories for compliance.

## 10. Real Customer Example
A wholesale agency connects their Google Drive. The system backs up their database daily, storing local files for 7 days and keeping unlimited archives on Google Drive. If an operator accidentally deletes an active broadcast list, the admin opens `/backups`, selects the latest snapshot, types `RESTORE` to confirm, and recovers the workspace data within minutes.

## 11. Customer Journey
Admin logs in &rarr; Triggers manual backup &rarr; Connects Google Drive sync &rarr; Selects backup recovery point &rarr; Confirms restoration with confirmation phrase &rarr; Database restored.

## 12. Inputs
- Confirmation keyword (`RESTORE`).
- Uploaded ZIP backup packages (for manual recovery uploads).
- Google Drive API authorization redirects.

## 13. Outputs
- Generated ZIP snapshot packages.
- Uploaded offsite archives.
- Restored database states.
- System audit records.

## 14. Dependencies
- **TenantBackup Model:** DB storage.
- **Storage Disk:** File streams.
- **BackupService:** Execution driver.

## 15. Permissions
- **Who can access this page:** Users with `manage-settings` permissions on plans including `backups`.
- **Who can view information:** Admins.
- **Who can edit/restore:** Admins.
- **Who cannot access it:** Managers, Support Agents.

## 16. Important Rules
- You must type the word "RESTORE" in all-caps to authorize database restorations.
- Local backups are automatically deleted after 7 days; connect Google Drive for permanent archives.

## 17. Common Problems
- **Problem:** "Platform-managed backup" error block.
  - **Possible reason:** The backup is marked as Model B (platform-managed), which restricts downloads to platform super-admins for security.
  - **What the user should do:** Contact support to request access to the file.
- **Problem:** Restore fails with "File not found" error.
  - **Possible reason:** The local backup file has expired (older than 7 days) and was deleted from the server, and no cloud integration was set up.
  - **What the user should do:** In the future, connect Google Drive to preserve older recovery points.

## 18. Simple Explanation for Sales
The Backup page is where you secure your data. You can save copies of your settings and contact lists, link your Google Drive to save daily copies automatically, and restore your system if anything gets deleted.

## 19. Simple Explanation for Marketing
Admins use this page to backup settings. If you accidentally delete campaign configurations, they can restore yesterday's copy to recover your campaigns.

## 20. Simple Explanation for Support
If customer logs or tickets are missing, your admin can use this page to restore data from a previous backup, recovering lost conversations.

## 21. Related Features
- [System Settings](./system-settings.md)
- [Audience CRM](./contacts.md)

## 22. Page Status
Current

## 23. Source of Truth
- **Page URL:** `/backups`
- **Implementation:** `App\Http\Controllers\Backup\BackupController` & `App\Http\Controllers\Backup\RestoreController`
- **Relevant files:** 
  - `routes/web.php`
  - `app/Http/Controllers/Backup/BackupController.php`
  - `app/Http/Controllers/Backup/RestoreController.php`
  - `resources/views/backups/index.blade.php`
  - `resources/views/backups/partials/restore_modal.blade.php`
  - `app/Models/TenantBackup.php`
  - `app/Services/BackupService.php`
- **Related documentation:** None currently linked.
- **Last reviewed:** 2026-08-17
