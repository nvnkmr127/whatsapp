# Feature Mapping (Backend → Frontend)

Generated from `php artisan route:list --json`.

Legend: CRUD = Create (C), Read (R), Update (U), Delete (D)

## Web Features
### activity

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `activity.index` | `GET|HEAD` | `/activity` | `Closure` | `` | `R` |

### admin

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `admin.dashboard` | `GET|HEAD` | `/admin` | `App\Http\Controllers\SuperAdminController@dashboard` | `` | `R` |
| `admin.audit-logs` | `GET|HEAD` | `/admin/audit-logs` | `App\Http\Controllers\SuperAdminController@auditLogs` | `` | `R` |
| `admin.email-logs.index` | `GET|HEAD` | `/admin/email-logs` | `App\Http\Controllers\Admin\EmailLogController@index` | `` | `R` |
| `admin.email-logs.show` | `GET|HEAD` | `/admin/email-logs/{log}` | `App\Http\Controllers\Admin\EmailLogController@show` | `` | `R` |
| `admin.email-templates.index` | `GET|HEAD` | `/admin/email-templates` | `App\Livewire\Admin\EmailTemplates\Index` | `` | `R` |
| `admin.email-templates.create` | `GET|HEAD` | `/admin/email-templates/create` | `App\Livewire\Admin\EmailTemplates\Create` | `` | `R` |
| `admin.email-templates.edit` | `GET|HEAD` | `/admin/email-templates/{template}` | `App\Livewire\Admin\EmailTemplates\Edit` | `` | `R` |
| `admin.impersonate.exit` | `GET|HEAD` | `/admin/impersonate/exit` | `App\Http\Controllers\Admin\ImpersonationController@exit` | `` | `R` |
| `admin.impersonate.enter` | `GET|HEAD` | `/admin/impersonate/{user}` | `App\Http\Controllers\Admin\ImpersonationController@enter` | `` | `R` |
| `admin.offer-settings` | `GET|HEAD` | `/admin/offer-settings` | `App\Livewire\Admin\OfferSettings` | `` | `R` |
| `admin.plans` | `GET|HEAD` | `/admin/plans` | `App\Livewire\Admin\PlanManager` | `` | `R` |
| `admin.tenants.store` | `POST` | `/admin/tenants` | `App\Http\Controllers\SuperAdminController@store` | `` | `C` |
| `admin.tenants.create` | `GET|HEAD` | `/admin/tenants/create` | `App\Http\Controllers\SuperAdminController@create` | `` | `R` |
| `admin.tenants.update` | `PUT` | `/admin/tenants/{id}` | `App\Http\Controllers\SuperAdminController@update` | `` | `U` |
| `admin.tenants.destroy` | `DELETE` | `/admin/tenants/{id}` | `App\Http\Controllers\SuperAdminController@destroy` | `` | `D` |
| `admin.tenants.edit` | `GET|HEAD` | `/admin/tenants/{id}/edit` | `App\Http\Controllers\SuperAdminController@edit` | `` | `R` |
| `admin.tenants.overrides.store` | `POST` | `/admin/tenants/{id}/overrides` | `App\Http\Controllers\SuperAdminController@storeOverride` | `` | `C` |
| `admin.tenants.overrides.destroy` | `DELETE` | `/admin/tenants/{id}/overrides/{overrideId}` | `App\Http\Controllers\SuperAdminController@deleteOverride` | `` | `D` |

### analytics

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `analytics` | `GET|HEAD` | `/analytics` | `App\Livewire\Analytics\AnalyticsDashboard` | `` | `R` |
| `analytics.events` | `GET|HEAD` | `/analytics/events` | `App\Livewire\Analytics\EventDashboard` | `` | `R` |
| `analytics.explorer` | `GET|HEAD` | `/analytics/explorer` | `App\Livewire\Analytics\EventExplorer` | `` | `R` |

### auth

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `auth.google.callback` | `GET|HEAD` | `/auth/google/callback` | `App\Http\Controllers\Auth\GoogleAuthController@callback` | `` | `R` |
| `auth.google.redirect` | `GET|HEAD` | `/auth/google/redirect` | `App\Http\Controllers\Auth\GoogleAuthController@redirect` | `` | `R` |
| `auth.otp.request` | `POST` | `/auth/otp/request` | `App\Http\Controllers\Auth\PasswordlessAuthController@requestOtp` | `` | `C` |
| `auth.otp.verify` | `GET|POST|HEAD` | `/auth/otp/verify` | `App\Http\Controllers\Auth\PasswordlessAuthController@verifyOtp` | `` | `CR` |

### automations

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `automations.index` | `GET|HEAD` | `/automations` | `App\Livewire\Automations\AutomationList` | `` | `R` |
| `automations.builder` | `GET|HEAD` | `/automations/builder/{automationId?}` | `App\Livewire\Automations\AutomationBuilder` | `` | `R` |

### backups

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `backups.index` | `GET|HEAD` | `/backups` | `App\Http\Controllers\Backup\BackupController@index` | `` | `R` |
| `backups.store` | `POST` | `/backups` | `App\Http\Controllers\Backup\BackupController@store` | `` | `C` |
| `backups.download` | `GET|HEAD` | `/backups/{id}/download` | `App\Http\Controllers\Backup\BackupController@download` | `` | `R` |
| `backups.restore` | `POST` | `/backups/{id}/restore` | `App\Http\Controllers\Backup\RestoreController@restore` | `` | `C` |

### billing

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `billing` | `GET|HEAD` | `/billing` | `App\Livewire\Billing\BillingDashboard` | `` | `R` |

### calls

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `calls.history` | `GET|HEAD` | `/calls` | `App\Livewire\Calls\CallHistory` | `` | `R` |
| `calls.analytics` | `GET|HEAD` | `/calls/analytics` | `App\Livewire\Calls\CallAnalytics` | `` | `R` |
| `calls.settings` | `GET|HEAD` | `/calls/settings` | `App\Livewire\Whatsapp\CallSettingsManager` | `` | `R` |

### campaigns

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `campaigns.index` | `GET|HEAD` | `/campaigns` | `App\Livewire\Campaigns\CampaignList` | `` | `R` |
| `campaigns.create` | `GET|HEAD` | `/campaigns/create` | `App\Livewire\Campaigns\Wizard` | `` | `R` |
| `campaigns.show` | `GET|HEAD` | `/campaigns/{campaignId}` | `App\Livewire\Campaigns\Show` | `` | `R` |
| `campaigns.live` | `GET|HEAD` | `/campaigns/{campaign}/live` | `App\Livewire\Campaigns\Dashboard` | `` | `R` |

### chat

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `chat` | `GET|HEAD` | `/chat` | `App\Livewire\Chat\ChatDashboard` | `` | `R` |

### commerce

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `commerce.dashboard` | `GET|HEAD` | `/commerce` | `App\Livewire\Commerce\Dashboard` | `` | `R` |
| `commerce.orders` | `GET|HEAD` | `/commerce/orders` | `App\Livewire\Commerce\OrderManager` | `` | `R` |
| `commerce.products` | `GET|HEAD` | `/commerce/products` | `App\Livewire\Commerce\ProductManager` | `` | `R` |
| `commerce.settings` | `GET|HEAD` | `/commerce/settings` | `App\Livewire\Commerce\CommerceSettings` | `` | `R` |

### compliance

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `compliance.index` | `GET|HEAD` | `/compliance` | `App\Livewire\Compliance\ComplianceManager` | `` | `R` |
| `compliance.logs` | `GET|HEAD` | `/compliance/logs` | `App\Http\Controllers\ComplianceController@logs` | `` | `R` |
| `compliance.registry` | `GET|HEAD` | `/compliance/registry` | `App\Http\Controllers\ComplianceController@registry` | `` | `R` |

### contacts

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `contacts.index` | `GET|HEAD` | `/contacts` | `Closure` | `` | `R` |

### current-team

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `current-team.update` | `PUT` | `/current-team` | `Laravel\Jetstream\Http\Controllers\CurrentTeamController@update` | `` | `U` |

### dashboard

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `dashboard` | `GET|HEAD` | `/dashboard` | `Closure` | `` | `R` |

### dev

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `dev.login` | `GET|HEAD` | `/dev/login/{email}` | `App\Http\Controllers\DevController@loginAs` | `` | `R` |

### developer

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `developer.overview` | `GET|HEAD` | `/developer` | `App\Livewire\Developer\DeveloperOverview` | `` | `R` |
| `developer.api-tokens` | `GET|HEAD` | `/developer/api-tokens` | `App\Livewire\Developer\ApiTokenManager` | `` | `R` |
| `developer.docs` | `GET|HEAD` | `/developer/docs` | `App\Http\Controllers\Developer\ApiDocumentationController@index` | `` | `R` |
| `webhook-sources.index` | `GET|HEAD` | `/developer/webhook-sources` | `App\Livewire\Developer\WebhookSourceManager` | `` | `R` |
| `developer.webhooks` | `GET|HEAD` | `/developer/webhooks` | `App\Livewire\Developer\WebhookManager` | `` | `R` |

### email

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `verification.send` | `POST` | `/email/verification-notification` | `Laravel\Fortify\Http\Controllers\EmailVerificationNotificationController@store` | `` | `C` |
| `verification.notice` | `GET|HEAD` | `/email/verify` | `Laravel\Fortify\Http\Controllers\EmailVerificationPromptController@__invoke` | `` | `R` |
| `verification.verify` | `GET|HEAD` | `/email/verify/{id}/{hash}` | `Laravel\Fortify\Http\Controllers\VerifyEmailController@__invoke` | `` | `R` |

### embed

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `embed.chat` | `GET|HEAD` | `/embed/chat` | `App\Http\Controllers\EmbedController@show` | `` | `R` |

### flows

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `flows.index` | `GET|HEAD` | `/flows` | `App\Livewire\Flows\FlowManager` | `` | `R` |
| `flows.builder` | `GET|HEAD` | `/flows/builder/{flowId?}` | `App\Livewire\Flows\FlowBuilder` | `` | `R` |

### forgot-password

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `password.request` | `GET|HEAD` | `/forgot-password` | `Laravel\Fortify\Http\Controllers\PasswordResetLinkController@create` | `` | `R` |
| `password.email` | `POST` | `/forgot-password` | `Laravel\Fortify\Http\Controllers\PasswordResetLinkController@store` | `` | `C` |

### identities

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `identities.destroy` | `DELETE` | `/identities/{identity}` | `App\Http\Controllers\UserIdentityController@destroy` | `` | `D` |

### integrations

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `integrations.ecommerce` | `GET|HEAD` | `/integrations/ecommerce` | `App\Livewire\Integrations\EcommerceIntegrations` | `` | `R` |
| `integrations.google-drive.callback` | `GET|HEAD` | `/integrations/google-drive/callback` | `App\Http\Controllers\Integrations\GoogleDriveController@callback` | `` | `R` |
| `integrations.google-drive.disconnect` | `POST` | `/integrations/google-drive/disconnect` | `App\Http\Controllers\Integrations\GoogleDriveController@disconnect` | `` | `C` |
| `integrations.google-drive.redirect` | `GET|HEAD` | `/integrations/google-drive/redirect` | `App\Http\Controllers\Integrations\GoogleDriveController@redirect` | `` | `R` |

### knowledge-base

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `knowledge-base.index` | `GET|HEAD` | `/knowledge-base` | `App\Livewire\Developer\KnowledgeBaseManager` | `` | `R` |
| `knowledge-base.feedback` | `GET|HEAD` | `/knowledge-base/feedback` | `App\Livewire\Developer\KnowledgeBaseFeedback` | `` | `R` |

### livewire

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `livewire.preview-file` | `GET|HEAD` | `/livewire/preview-file/{filename}` | `Livewire\Features\SupportFileUploads\FilePreviewController@handle` | `` | `R` |
| `livewire.update` | `POST` | `/livewire/update` | `Livewire\Mechanisms\HandleRequests\HandleRequests@handleUpdate` | `` | `C` |
| `livewire.upload-file` | `POST` | `/livewire/upload-file` | `Livewire\Features\SupportFileUploads\FileUploadController@handle` | `` | `C` |

### login

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `login` | `GET|HEAD` | `/login` | `Laravel\Fortify\Http\Controllers\AuthenticatedSessionController@create` | `` | `R` |
| `login.store` | `POST` | `/login` | `Laravel\Fortify\Http\Controllers\AuthenticatedSessionController@store` | `` | `C` |

### logout

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `logout` | `POST` | `/logout` | `Laravel\Fortify\Http\Controllers\AuthenticatedSessionController@destroy` | `` | `C` |

### register

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `register` | `GET|HEAD` | `/register` | `Laravel\Fortify\Http\Controllers\RegisteredUserController@create` | `` | `R` |
| `register.store` | `POST` | `/register` | `Laravel\Fortify\Http\Controllers\RegisteredUserController@store` | `` | `C` |

### reset-password

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `password.update` | `POST` | `/reset-password` | `Laravel\Fortify\Http\Controllers\NewPasswordController@store` | `` | `C` |
| `password.reset` | `GET|HEAD` | `/reset-password/{token}` | `Laravel\Fortify\Http\Controllers\NewPasswordController@create` | `` | `R` |

### sanctum

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `sanctum.csrf-cookie` | `GET|HEAD` | `/sanctum/csrf-cookie` | `Laravel\Sanctum\Http\Controllers\CsrfCookieController@show` | `` | `R` |

### settings

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `settings.hub` | `GET|HEAD` | `/settings` | `App\Livewire\Settings\SettingsHub` | `` | `R` |
| `settings.ai` | `GET|HEAD` | `/settings/ai` | `App\Livewire\Settings\AiSettings` | `` | `R` |
| `settings.canned-messages` | `GET|HEAD` | `/settings/canned-messages` | `App\Livewire\Settings\CannedMessageManager` | `` | `R` |
| `settings.categories` | `GET|HEAD` | `/settings/categories` | `App\Livewire\Settings\CategoryManager` | `` | `R` |
| `settings.chat-routing` | `GET|HEAD` | `/settings/chat-routing` | `App\Livewire\Settings\ChatRouting` | `` | `R` |
| `settings.system` | `GET|HEAD` | `/settings/system` | `App\Livewire\Settings\SystemSettings` | `` | `R` |

### storage

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `storage.local` | `GET|HEAD` | `/storage/{path}` | `Closure` | `` | `R` |

### team

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `teams.members` | `GET|HEAD` | `/team/members` | `App\Livewire\Teams\MembersManager` | `` | `R` |

### team-invitations

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `team-invitations.accept` | `GET|HEAD` | `/team-invitations/{invitation}` | `Laravel\Jetstream\Http\Controllers\TeamInvitationController@accept` | `` | `R` |

### teams

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `teams.create` | `GET|HEAD` | `/teams/create` | `Laravel\Jetstream\Http\Controllers\Livewire\TeamController@create` | `` | `R` |
| `teams.show` | `GET|HEAD` | `/teams/{team}` | `Laravel\Jetstream\Http\Controllers\Livewire\TeamController@show` | `` | `R` |

### templates

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `templates.index` | `GET|HEAD` | `/templates` | `Closure` | `` | `R` |

### two-factor-challenge

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `two-factor.login` | `GET|HEAD` | `/two-factor-challenge` | `Laravel\Fortify\Http\Controllers\TwoFactorAuthenticatedSessionController@create` | `` | `R` |
| `two-factor.login.store` | `POST` | `/two-factor-challenge` | `Laravel\Fortify\Http\Controllers\TwoFactorAuthenticatedSessionController@store` | `` | `C` |

### unsubscribe

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `marketing.unsubscribe` | `GET|HEAD` | `/unsubscribe/marketing` | `App\Http\Controllers\MarketingUnsubscribeController@unsubscribe` | `` | `R` |

### user

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `api-tokens.index` | `GET|HEAD` | `/user/api-tokens` | `Laravel\Jetstream\Http\Controllers\Livewire\ApiTokenController@index` | `` | `R` |
| `password.confirm` | `GET|HEAD` | `/user/confirm-password` | `Laravel\Fortify\Http\Controllers\ConfirmablePasswordController@show` | `` | `R` |
| `password.confirm.store` | `POST` | `/user/confirm-password` | `Laravel\Fortify\Http\Controllers\ConfirmablePasswordController@store` | `` | `C` |
| `password.confirmation` | `GET|HEAD` | `/user/confirmed-password-status` | `Laravel\Fortify\Http\Controllers\ConfirmedPasswordStatusController@show` | `` | `R` |
| `two-factor.confirm` | `POST` | `/user/confirmed-two-factor-authentication` | `Laravel\Fortify\Http\Controllers\ConfirmedTwoFactorAuthenticationController@store` | `` | `C` |
| `user-password.update` | `PUT` | `/user/password` | `Laravel\Fortify\Http\Controllers\PasswordController@update` | `` | `U` |
| `profile.show` | `GET|HEAD` | `/user/profile` | `Laravel\Jetstream\Http\Controllers\Livewire\UserProfileController@show` | `` | `R` |
| `user-profile-information.update` | `PUT` | `/user/profile-information` | `Laravel\Fortify\Http\Controllers\ProfileInformationController@update` | `` | `U` |
| `two-factor.enable` | `POST` | `/user/two-factor-authentication` | `Laravel\Fortify\Http\Controllers\TwoFactorAuthenticationController@store` | `` | `C` |
| `two-factor.disable` | `DELETE` | `/user/two-factor-authentication` | `Laravel\Fortify\Http\Controllers\TwoFactorAuthenticationController@destroy` | `` | `D` |
| `two-factor.qr-code` | `GET|HEAD` | `/user/two-factor-qr-code` | `Laravel\Fortify\Http\Controllers\TwoFactorQrCodeController@show` | `` | `R` |
| `two-factor.recovery-codes` | `GET|HEAD` | `/user/two-factor-recovery-codes` | `Laravel\Fortify\Http\Controllers\RecoveryCodeController@index` | `` | `R` |
| `two-factor.regenerate-recovery-codes` | `POST` | `/user/two-factor-recovery-codes` | `Laravel\Fortify\Http\Controllers\RecoveryCodeController@store` | `` | `C` |
| `two-factor.secret-key` | `GET|HEAD` | `/user/two-factor-secret-key` | `Laravel\Fortify\Http\Controllers\TwoFactorSecretKeyController@show` | `` | `R` |

### webhook-workflows

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `webhooks.report` | `GET|HEAD` | `/webhook-workflows/{workflowId}/report` | `App\Livewire\Webhooks\WebhookReport` | `` | `R` |

### webhooks

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `webhooks.logs` | `GET|HEAD` | `/webhooks/logs` | `App\Livewire\Webhooks\WebhookLogs` | `` | `R` |

### whatsapp

| Route Name | Method | URI | Controller / Action | Blade View | CRUD |
| --- | --- | --- | --- | --- | --- |
| `teams.inbox_settings` | `GET|HEAD` | `/whatsapp/inbox` | `Closure` | `` | `R` |
| `whatsapp.onboard.exchange` | `POST` | `/whatsapp/onboard/exchange` | `App\Http\Controllers\WhatsAppOnboardingController@exchangeToken` | `` | `C` |
| `teams.whatsapp_opt_in` | `GET|HEAD` | `/whatsapp/opt-in` | `App\Livewire\Teams\OptInManagement` | `` | `R` |
| `teams.whatsapp_config` | `GET|HEAD` | `/whatsapp/setup` | `Closure` | `` | `R` |

## API Features
### api/api

| Method | URI | Controller / Action | CRUD |
| --- | --- | --- | --- |
| `GET|HEAD` | `/api/user` | `Closure` | `R` |
| `GET|HEAD` | `/api/webhook/whatsapp` | `App\Http\Controllers\WhatsAppWebhookController@verify` | `R` |
| `POST` | `/api/webhook/whatsapp` | `App\Http\Controllers\WhatsAppWebhookController@handle` | `C` |
| `GET|HEAD` | `/api/webhook/whatsapp/calls` | `App\Http\Controllers\Webhooks\WhatsAppCallWebhookController@verify` | `R` |
| `POST` | `/api/webhook/whatsapp/calls` | `App\Http\Controllers\Webhooks\WhatsAppCallWebhookController@handle` | `C` |
| `POST` | `/api/webhooks/custom/orders` | `App\Http\Controllers\Webhooks\CustomSiteWebhookController@handle` | `C` |
| `GET|HEAD` | `/api/webhooks/meta/commerce` | `App\Http\Controllers\Webhooks\MetaCommerceWebhookController@verify` | `R` |
| `POST` | `/api/webhooks/meta/commerce` | `App\Http\Controllers\Webhooks\MetaCommerceWebhookController@handle` | `C` |
| `POST` | `/api/webhooks/shopify/orders` | `App\Http\Controllers\Webhooks\ShopifyWebhookController@handle` | `C` |
| `POST` | `/api/webhooks/trigger/{id}` | `App\Http\Controllers\Api\WebhookTriggerController@trigger` | `C` |
| `POST` | `/api/webhooks/woocommerce/orders` | `App\Http\Controllers\Webhooks\WooCommerceWebhookController@handle` | `C` |
| `POST` | `/api/whatsapp/flow` | `App\Http\Controllers\WhatsAppFlowController@handle` | `C` |

### api/calls

| Method | URI | Controller / Action | CRUD |
| --- | --- | --- | --- |
| `GET|HEAD` | `/api/v1/calls` | `App\Http\Controllers\Api\CallController@index` | `R` |
| `GET|HEAD` | `/api/v1/calls/active` | `App\Http\Controllers\Api\CallController@active` | `R` |
| `POST` | `/api/v1/calls/check-eligibility` | `App\Http\Controllers\Api\CallController@checkEligibility` | `C` |
| `GET|HEAD` | `/api/v1/calls/contacts/{contactId}/history` | `App\Http\Controllers\Api\CallController@contactHistory` | `R` |
| `POST` | `/api/v1/calls/initiate` | `App\Http\Controllers\Api\CallController@initiate` | `C` |
| `GET|HEAD` | `/api/v1/calls/statistics` | `App\Http\Controllers\Api\CallController@statistics` | `R` |
| `GET|HEAD` | `/api/v1/calls/{callId}` | `App\Http\Controllers\Api\CallController@show` | `R` |
| `POST` | `/api/v1/calls/{callId}/answer` | `App\Http\Controllers\Api\CallController@answer` | `C` |
| `POST` | `/api/v1/calls/{callId}/end` | `App\Http\Controllers\Api\CallController@end` | `C` |
| `POST` | `/api/v1/calls/{callId}/reject` | `App\Http\Controllers\Api\CallController@reject` | `C` |

### api/contacts

| Method | URI | Controller / Action | CRUD |
| --- | --- | --- | --- |
| `GET|HEAD` | `/api/v1/contacts` | `App\Http\Controllers\Api\ExternalContactController@index` | `R` |
| `POST` | `/api/v1/contacts` | `App\Http\Controllers\Api\ExternalContactController@store` | `C` |

### api/conversations

| Method | URI | Controller / Action | CRUD |
| --- | --- | --- | --- |
| `POST` | `/api/v1/conversations/{conversation}/heartbeat` | `App\Http\Controllers\ConversationController@heartbeat` | `C` |
| `POST` | `/api/v1/conversations/{conversation}/lock` | `App\Http\Controllers\ConversationController@lock` | `C` |
| `POST` | `/api/v1/conversations/{conversation}/takeover` | `App\Http\Controllers\ConversationController@forceTakeOver` | `C` |
| `POST` | `/api/v1/conversations/{conversation}/unlock` | `App\Http\Controllers\ConversationController@unlock` | `C` |
| `GET|HEAD` | `/api/v1/conversations/{phone}` | `App\Http\Controllers\ExternalConversationController@index` | `R` |

### api/ecommerce

| Method | URI | Controller / Action | CRUD |
| --- | --- | --- | --- |
| `GET|HEAD` | `/api/v1/ecommerce/integrations/{integration}/health` | `App\Http\Controllers\Api\EcommerceIntegrationController@health` | `R` |
| `GET|HEAD` | `/api/v1/ecommerce/integrations/{integration}/sessions` | `App\Http\Controllers\Api\EcommerceIntegrationController@sessions` | `R` |
| `PATCH` | `/api/v1/ecommerce/integrations/{integration}/settings` | `App\Http\Controllers\Api\EcommerceIntegrationController@updateSettings` | `U` |
| `POST` | `/api/v1/ecommerce/integrations/{integration}/sync` | `App\Http\Controllers\Api\EcommerceIntegrationController@sync` | `C` |

### api/embed-token

| Method | URI | Controller / Action | CRUD |
| --- | --- | --- | --- |
| `POST` | `/api/v1/embed-token` | `App\Http\Controllers\EmbedController@generateToken` | `C` |

### api/inbox

| Method | URI | Controller / Action | CRUD |
| --- | --- | --- | --- |
| `GET|HEAD` | `/api/v1/inbox/contacts/resolve` | `App\Http\Controllers\Api\InboxContactController@resolve` | `R` |
| `POST` | `/api/v1/inbox/contacts/resolve-batch` | `App\Http\Controllers\Api\InboxContactController@resolveBatch` | `C` |
| `PUT` | `/api/v1/inbox/contacts/{contact}` | `App\Http\Controllers\Api\InboxContactController@update` | `U` |
| `POST` | `/api/v1/inbox/contacts/{contact}/assign` | `App\Http\Controllers\Api\InboxContactController@assign` | `C` |

### api/messages

| Method | URI | Controller / Action | CRUD |
| --- | --- | --- | --- |
| `POST` | `/api/v1/messages` | `App\Http\Controllers\ExternalConversationController@send` | `C` |

### api/otp

| Method | URI | Controller / Action | CRUD |
| --- | --- | --- | --- |
| `POST` | `/api/v1/otp/verify` | `App\Http\Controllers\Api\OTPVerificationController@verify` | `C` |

### api/products

| Method | URI | Controller / Action | CRUD |
| --- | --- | --- | --- |
| `POST` | `/api/v1/products/{product}/lock` | `App\Http\Controllers\Api\EcommerceIntegrationController@lockField` | `C` |

### api/templates

| Method | URI | Controller / Action | CRUD |
| --- | --- | --- | --- |
| `GET|HEAD` | `/api/v1/templates` | `App\Http\Controllers\Api\ExternalTemplateController@index` | `R` |

### api/webhooks

| Method | URI | Controller / Action | CRUD |
| --- | --- | --- | --- |
| `POST` | `/api/v1/webhooks/inbound` | `App\Http\Controllers\Api\InboundWebhookController@handle` | `C` |
| `GET|HEAD` | `/api/v1/webhooks/inbound/url` | `App\Http\Controllers\Api\InboundWebhookController@getUrl` | `R` |
| `POST` | `/api/v1/webhooks/inbound/{source}` | `App\Http\Controllers\Api\InboundWebhookController@handleSource` | `C` |
| `GET|HEAD` | `/api/v1/webhooks/sources/{source}/url` | `App\Http\Controllers\Api\InboundWebhookController@getSourceUrl` | `R` |

### api/whatsapp

| Method | URI | Controller / Action | CRUD |
| --- | --- | --- | --- |
| `POST` | `/api/v1/whatsapp/calls/generate-link` | `App\Http\Controllers\CallSettingsController@generateLink` | `C` |
| `POST` | `/api/v1/whatsapp/calls/initiate` | `App\Http\Controllers\CallPermissionController@initiateCall` | `C` |
| `GET|HEAD` | `/api/v1/whatsapp/calls/permission/{contactId}` | `App\Http\Controllers\CallPermissionController@checkPermission` | `R` |
| `POST` | `/api/v1/whatsapp/calls/request-permission` | `App\Http\Controllers\CallPermissionController@requestPermission` | `C` |
| `POST` | `/api/v1/whatsapp/{phoneNumberId}/settings` | `App\Http\Controllers\CallSettingsController@update` | `C` |
| `GET|HEAD` | `/api/v1/whatsapp/{phoneNumberId}/settings` | `App\Http\Controllers\CallSettingsController@show` | `R` |
