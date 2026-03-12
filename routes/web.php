<?php

use App\Http\Controllers\LeadCaptureWidgetController;
use Illuminate\Support\Facades\Route;
use App\Livewire\Developer\KnowledgeBaseManager;
use App\Livewire\Settings\AiSettings;
use App\Http\Controllers\Integrations\GoogleDriveController;

Route::get('/', function () {
    return redirect()->to('https://watxio.com/');
});

Route::get('/unsubscribe/marketing', [\App\Http\Controllers\MarketingUnsubscribeController::class, 'unsubscribe'])->name('marketing.unsubscribe');


// Passwordless Auth Routes
Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('/otp/request', [\App\Http\Controllers\Auth\PasswordlessAuthController::class, 'requestOtp'])
        ->middleware('throttle:3,10') // Max 3 requests per 10 minutes
        ->name('otp.request');

    Route::match(['get', 'post'], '/otp/verify', [\App\Http\Controllers\Auth\PasswordlessAuthController::class, 'verifyOtp'])
        ->middleware('throttle:5,1') // Max 5 verification attempts per minute
        ->name('otp.verify');

    // Google OAuth Routes
    Route::get('/google/redirect', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'redirect'])->name('google.redirect');
    Route::get('/google/callback', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'callback'])->name('google.callback');

    // Facebook OAuth Routes
    Route::get('/facebook/redirect', [\App\Http\Controllers\Auth\FacebookAuthController::class, 'redirect'])->name('facebook.redirect');
    Route::get('/facebook/callback', [\App\Http\Controllers\Auth\FacebookAuthController::class, 'callback'])->name('facebook.callback');
});

// Lead Capture QR Routes (Public)
Route::get('/qr/{slug}', [LeadCaptureWidgetController::class, 'show'])->name('qr.show');
Route::get('/qr/{slug}/image', [LeadCaptureWidgetController::class, 'qr'])->name('qr.image');
Route::post('/qr/{slug}/lead', [LeadCaptureWidgetController::class, 'lead'])->name('qr.lead');
Route::get('/growth-tools/config/{slug}', [LeadCaptureWidgetController::class, 'config'])->name('qr.config');
Route::get('/growth-tools/click/{slug}', [LeadCaptureWidgetController::class, 'trackClick'])->name('qr.click');

Route::middleware([
    'auth:sanctum',
    'Laravel\Jetstream\Http\Middleware\AuthenticateSession',
    'verified',
    'subscription',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/growth-tools', \App\Livewire\LeadCapture\WidgetManager::class)->name('growth-tools')->middleware(['can:manage-campaigns']);

    // Webhooks - See developer section for webhook management routes
    Route::get('/webhooks/logs', \App\Livewire\Webhooks\WebhookLogs::class)->name('webhooks.logs')->middleware(['can:manage-settings', 'plan_feature:webhooks']);
    Route::get('/webhook-workflows/{workflowId}/report', \App\Livewire\Webhooks\WebhookReport::class)->name('webhooks.report');

    // WhatsApp Config (Admins Only)
    Route::get('/whatsapp/setup', function () {
        return view('teams.whatsapp-config');
    })->name('teams.whatsapp_config')->middleware('can:manage-settings');

    Route::get('/whatsapp/inbox', function () {
        return view('teams.inbox-settings');
    })->name('teams.inbox_settings')->middleware('can:manage-settings');

    Route::get('/whatsapp/opt-in', \App\Livewire\Teams\OptInManagement::class)->name('teams.whatsapp_opt_in')->middleware('can:manage-settings');

    // AI Business Brain
    Route::get('/knowledge-base', KnowledgeBaseManager::class)->name('knowledge-base.index')->middleware(['can:manage-settings', 'plan_feature:ai']);
    Route::get('/knowledge-base/feedback', \App\Livewire\Developer\KnowledgeBaseFeedback::class)->name('knowledge-base.feedback')->middleware(['can:manage-settings', 'plan_feature:ai']);
    Route::get('/settings', \App\Livewire\Settings\SettingsHub::class)->name('settings.hub')->middleware('can:manage-settings');
    Route::get('/settings/ai', AiSettings::class)->name('settings.ai')->middleware(['can:manage-settings', 'plan_feature:ai']);
    Route::get('/settings/system', \App\Livewire\Settings\SystemSettings::class)->name('settings.system')->middleware('can:manage-settings');
    Route::get('/settings/categories', \App\Livewire\Settings\CategoryManager::class)->name('settings.categories')->middleware('can:manage-settings');
    Route::get('/settings/canned-messages', \App\Livewire\Settings\CannedMessageManager::class)->name('settings.canned-messages')->middleware('can:manage-settings');
    Route::get('/settings/chat-routing', \App\Livewire\Settings\ChatRouting::class)->name('settings.chat-routing')->middleware('can:manage-settings');
    Route::get('/settings/onboarding-automation', \App\Livewire\Settings\OnboardingAutomation::class)
        ->name('settings.onboarding-automation')
        ->middleware(['can:manage-settings', \App\Http\Middleware\EnsureUserIsSuperAdmin::class]);

    Route::post('/whatsapp/onboard/exchange', [\App\Http\Controllers\WhatsAppOnboardingController::class, 'exchangeToken'])
        ->name('whatsapp.onboard.exchange')
        ->middleware('can:manage-settings');

    Route::get('/team/members', \App\Livewire\Teams\MembersManager::class)->name('teams.members');

    // Super Admin Routes
    Route::middleware([\App\Http\Middleware\EnsureUserIsSuperAdmin::class])->group(function () {
        Route::get('/admin', [\App\Http\Controllers\SuperAdminController::class, 'dashboard'])->name('admin.dashboard');
        Route::get('/admin/tenants/create', [\App\Http\Controllers\SuperAdminController::class, 'create'])->name('admin.tenants.create');
        Route::post('/admin/tenants', [\App\Http\Controllers\SuperAdminController::class, 'store'])->name('admin.tenants.store');
        Route::get('/admin/tenants/{id}/edit', [\App\Http\Controllers\SuperAdminController::class, 'edit'])->name('admin.tenants.edit');
        Route::put('/admin/tenants/{id}', [\App\Http\Controllers\SuperAdminController::class, 'update'])->name('admin.tenants.update');
        Route::delete('/admin/tenants/{id}', [\App\Http\Controllers\SuperAdminController::class, 'destroy'])->name('admin.tenants.destroy');
        Route::get('/admin/audit-logs', [\App\Http\Controllers\SuperAdminController::class, 'auditLogs'])->name('admin.audit-logs');
        Route::get('/admin/plans', \App\Livewire\Admin\PlanManager::class)->name('admin.plans');
        Route::post('/admin/tenants/{id}/overrides', [\App\Http\Controllers\SuperAdminController::class, 'storeOverride'])->name('admin.tenants.overrides.store');
        Route::delete('/admin/tenants/{id}/overrides/{overrideId}', [\App\Http\Controllers\SuperAdminController::class, 'deleteOverride'])->name('admin.tenants.overrides.destroy');

        // Impersonation
        Route::get('/admin/impersonate/{user}', [\App\Http\Controllers\Admin\ImpersonationController::class, 'enter'])->name('admin.impersonate.enter');

        // Launch Offer Settings
        Route::get('/admin/offer-settings', \App\Livewire\Admin\OfferSettings::class)->name('admin.offer-settings');

        // Entitlement debug: full snapshot for any team (Super Admin only)
        Route::get('/admin/entitlement/{team}', function (\App\Models\Team $team) {
            $snapshot = app(\App\Services\EntitlementService::class)->for($team)->toArray();
            return response()->json($snapshot, 200, ['Content-Type' => 'application/json'], JSON_PRETTY_PRINT);
        })->name('admin.entitlement.debug');

        // Offer settings schema (for UI generation / debugging)
        Route::get('/admin/offer-settings/schema', function () {
            return response()->json(
                app(\App\Services\OfferSettingsService::class)->schema()->toArray(),
                200,
                ['Content-Type' => 'application/json'],
                JSON_PRETTY_PRINT
            );
        })->name('admin.offer-settings.schema');

        // CRM (System-wide User Management)
        Route::get('/admin/crm', \App\Livewire\Crm\CrmHub::class)->name('admin.crm');

        // Automation Workflows (Super Admin)
        Route::get('/admin/workflows', \App\Livewire\Workflows\WorkflowManager::class)->name('workflows.index');

        // Email Templates (System)
        Route::get('/admin/email-templates', \App\Livewire\Admin\EmailTemplates\Index::class)->name('admin.email-templates.index');
        Route::get('/admin/email-templates/create', \App\Livewire\Admin\EmailTemplates\Create::class)->name('admin.email-templates.create');
        Route::get('/admin/email-templates/{template}', \App\Livewire\Admin\EmailTemplates\Edit::class)->name('admin.email-templates.edit');

        // Email Logs (Tracking)
        Route::get('/admin/email-logs', [\App\Http\Controllers\Admin\EmailLogController::class, 'index'])->name('admin.email-logs.index');
        Route::get('/admin/email-logs/{log}', [\App\Http\Controllers\Admin\EmailLogController::class, 'show'])->name('admin.email-logs.show');

        // System Health Dashboard
        Route::get('/admin/health', [\App\Http\Controllers\Admin\SystemHealthController::class, 'index'])->name('admin.health');
        Route::post('/admin/health/jobs/retry', [\App\Http\Controllers\Admin\SystemHealthController::class, 'retryJobs'])->name('admin.health.jobs.retry');
        Route::post('/admin/health/jobs/clear', [\App\Http\Controllers\Admin\SystemHealthController::class, 'clearJobs'])->name('admin.health.jobs.clear');
    });

    // Logout and Exit Impersonation (Universal)
    Route::get('/admin/impersonate/exit', [\App\Http\Controllers\Admin\ImpersonationController::class, 'exit'])->name('admin.impersonate.exit');

    // Agent Console (Agents, Managers, Admins)
    Route::get('/chat', \App\Livewire\Chat\ChatDashboard::class)->name('chat')->middleware(['can:chat-access', 'plan_feature:chat']);

    // Conversation Locks (Session Auth for Web)
    Route::post('/api/v1/conversations/{id}/lock', [\App\Http\Controllers\Api\ConversationLockController::class, 'lock']);
    Route::post('/api/v1/conversations/{id}/unlock', [\App\Http\Controllers\Api\ConversationLockController::class, 'unlock']);
    Route::post('/api/v1/conversations/{id}/heartbeat', [\App\Http\Controllers\Api\ConversationLockController::class, 'heartbeat']);
    Route::post('/api/v1/conversations/{id}/takeover', [\App\Http\Controllers\Api\ConversationLockController::class, 'takeover']);

    // Unified CRM (Managers, Admins)
    Route::get('/contacts', function () {
        return view('contacts.index');
    })->name('contacts.index')->middleware(['can:manage-contacts', 'plan_feature:contacts']);

    // Marketing & Funnels (Managers, Admins) - Requires 'campaigns' feature
    Route::get('/campaigns', \App\Livewire\Campaigns\CampaignList::class)->name('campaigns.index')->middleware(['can:manage-campaigns', 'plan_feature:campaigns']);

    Route::get('/campaigns/create', \App\Livewire\Campaigns\Wizard::class)->name('campaigns.create')->middleware(['can:manage-campaigns', 'plan_feature:campaigns']);

    Route::get('/campaigns/{campaignId}', \App\Livewire\Campaigns\Show::class)->name('campaigns.show')->middleware(['can:manage-campaigns', 'plan_feature:campaigns']);

    Route::get('/campaigns/{campaign}/live', \App\Livewire\Campaigns\Dashboard::class)->name('campaigns.live')->middleware(['can:manage-campaigns', 'plan_feature:campaigns']);

    Route::get('/templates', function () {
        return view('templates.index');
    })->name('templates.index')->middleware(['can:manage-templates', 'plan_feature:templates']);

    // Compliance Modules
    Route::get('/compliance', \App\Livewire\Compliance\ComplianceManager::class)->name('compliance.index')->middleware('can:manage-settings');
    Route::get('/compliance/logs', [\App\Http\Controllers\ComplianceController::class, 'logs'])->name('compliance.logs')->middleware('can:manage-settings');
    Route::get('/compliance/registry', [\App\Http\Controllers\ComplianceController::class, 'registry'])->name('compliance.registry')->middleware('can:manage-settings');

    Route::get('/automations', \App\Livewire\Automations\AutomationList::class)->name('automations.index')->middleware(['can:manage-campaigns', 'plan_feature:automations']);

    Route::get('/automations/{automationId}/analytics', \App\Livewire\Automations\AutomationAnalytics::class)->name('automations.analytics')->middleware(['can:manage-campaigns', 'plan_feature:automations']);

    Route::get('/automations/builder/{automationId?}', \App\Livewire\Automations\AutomationBuilder::class)->name('automations.builder')->middleware(['can:manage-campaigns', 'plan_feature:automations']);

    // WhatsApp Flows
    Route::get('/flows', \App\Livewire\Flows\FlowManager::class)->name('flows.index')->middleware(['can:manage-campaigns', 'plan_feature:flows']);
    Route::get('/flows/builder/{flowId?}', \App\Livewire\Flows\FlowBuilder::class)->name('flows.builder')->middleware(['can:manage-campaigns', 'plan_feature:flows']);

    Route::get('/analytics', \App\Livewire\Analytics\AnalyticsDashboard::class)->name('analytics')->middleware(['can:manage-settings', 'plan_feature:analytics']);
    Route::get('/analytics/events', \App\Livewire\Analytics\EventDashboard::class)->name('analytics.events')->middleware(['can:manage-settings', 'plan_feature:analytics']);
    Route::get('/analytics/explorer', \App\Livewire\Analytics\EventExplorer::class)->name('analytics.explorer')->middleware(['can:manage-settings', 'plan_feature:analytics']);
    Route::get('/analytics/templates', \App\Livewire\Analytics\TemplateHeatmap::class)->name('analytics.templates')->middleware(['can:manage-settings', 'plan_feature:analytics']);
    Route::get('/analytics/cohorts', \App\Livewire\Analytics\CohortAnalysis::class)->name('analytics.cohorts')->middleware(['can:manage-settings', 'plan_feature:analytics']);

    // WhatsApp Calling
    Route::get('/calls', \App\Livewire\Calls\CallHistory::class)->name('calls.history')->middleware('can:chat-access');
    Route::get('/calls/analytics', \App\Livewire\Calls\CallAnalytics::class)->name('calls.analytics')->middleware('can:manage-settings');
    Route::get('/calls/settings', \App\Livewire\Whatsapp\CallSettingsManager::class)->name('calls.settings')->middleware('can:manage-settings');

    // Billing Dashboard
    Route::get('/billing', \App\Livewire\Billing\BillingDashboard::class)->name('billing');

    Route::get('/activity', function () {
        return view('activity.index');
    })->name('activity.index')->middleware('can:manage-settings');

    // Developer Portal
    Route::get('/developer', \App\Livewire\Developer\DeveloperOverview::class)->name('developer.overview')->middleware(['can:manage-settings', 'plan_feature:api_access']);
    Route::get('/developer/webhooks', \App\Livewire\Developer\WebhookManager::class)->name('developer.webhooks')->middleware(['can:manage-settings', 'plan_feature:webhooks']);
    Route::get('/developer/webhook-sources', \App\Livewire\Developer\WebhookSourceManager::class)->name('webhook-sources.index')->middleware(['can:manage-settings', 'plan_feature:webhooks']);
    Route::get('/developer/api-tokens', \App\Livewire\Developer\ApiTokenManager::class)->name('developer.api-tokens')->middleware(['can:manage-settings', 'plan_feature:api_access']);
    Route::get('/developer/teams', \App\Livewire\Developer\TeamExplorer::class)->name('developer.teams')->middleware([\App\Http\Middleware\EnsureUserIsSuperAdmin::class]);
    Route::get('/developer/docs', [\App\Http\Controllers\Developer\ApiDocumentationController::class, 'index'])->name('developer.docs')->middleware('plan_feature:api_access');
    // Commerce
    // Commerce - Requires 'commerce' feature
    Route::get('/commerce', \App\Livewire\Commerce\Dashboard::class)->name('commerce.dashboard')->middleware(['can:manage-campaigns', 'plan_feature:commerce']);
    Route::get('/commerce/orders', \App\Livewire\Commerce\OrderManager::class)->name('commerce.orders')->middleware(['can:manage-campaigns', 'plan_feature:commerce']);
    Route::get('/commerce/products', \App\Livewire\Commerce\ProductManager::class)->name('commerce.products')->middleware(['can:manage-campaigns', 'plan_feature:commerce']);
    Route::get('/commerce/settings', \App\Livewire\Commerce\CommerceSettings::class)->name('commerce.settings')->middleware(['can:manage-settings', 'plan_feature:commerce']);
    Route::get('/integrations/ecommerce', \App\Livewire\Integrations\EcommerceIntegrations::class)->name('integrations.ecommerce')->middleware(['can:manage-settings', 'plan_feature:commerce']);

    // Ads Manager (New)
    Route::get('/ads/manager', \App\Livewire\Ads\MetaAdsManager::class)->name('ads.manager')->middleware(['can:manage-campaigns', 'plan_feature:campaigns']);
    Route::get('/ads/create/{adAccountId?}', \App\Livewire\Ads\CreateAdCampaign::class)->name('ads.create')->middleware(['can:manage-campaigns', 'plan_feature:campaigns']);

    // Google Drive Integration
    Route::prefix('integrations/google-drive')->name('integrations.google-drive.')->group(function () {
        Route::get('/redirect', [GoogleDriveController::class, 'redirect'])->name('redirect');
        Route::get('/callback', [GoogleDriveController::class, 'callback'])->name('callback');
        Route::post('/disconnect', [GoogleDriveController::class, 'disconnect'])->name('disconnect');
    });

    // ─────────────────────────────────────────────────────────────────────
    // Backup & Restore — Admin Only (manage-settings permission required)
    // ─────────────────────────────────────────────────────────────────────
    Route::middleware(['can:manage-settings'])->group(function () {
        Route::get('/backups', [\App\Http\Controllers\Backup\BackupController::class, 'index'])->name('backups.index');
        Route::post('/backups', [\App\Http\Controllers\Backup\BackupController::class, 'store'])->name('backups.store');
        Route::get('/backups/{id}/download', [\App\Http\Controllers\Backup\BackupController::class, 'download'])->name('backups.download');
        Route::post('/backups/{id}/restore', [\App\Http\Controllers\Backup\RestoreController::class, 'restore'])->name('backups.restore');
    });

    // Signed download stream
    Route::get('/backups/{id}/download/stream', [\App\Http\Controllers\Backup\SecureDownloadController::class, 'stream'])
        ->name('backups.download.stream')
        ->middleware(['signed']);

    // Identity Management
    Route::delete('/identities/{identity}', [\App\Http\Controllers\UserIdentityController::class, 'destroy'])->name('identities.destroy');

});

// Embed Routes (Publicly accessible but Token protected internally)
Route::get('/embed/chat', [\App\Http\Controllers\EmbedController::class, 'show'])->name('embed.chat');