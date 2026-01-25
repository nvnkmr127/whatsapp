<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Developer\KnowledgeBaseManager;
use App\Livewire\Settings\AiSettings;
use App\Http\Controllers\Integrations\GoogleDriveController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/unsubscribe/marketing', [\App\Http\Controllers\MarketingUnsubscribeController::class, 'unsubscribe'])->name('marketing.unsubscribe');

Route::get('/dev/login/{email}', [\App\Http\Controllers\DevController::class, 'loginAs'])->name('dev.login');

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
});

Route::middleware([
    'auth:sanctum',
    'Laravel\Jetstream\Http\Middleware\AuthenticateSession',
    'verified',
    'subscription',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Webhooks
    Route::get('/webhooks', \App\Livewire\Developer\WebhookSourceManager::class)->name('webhooks.index');
    Route::get('/webhooks/logs', \App\Livewire\Webhooks\WebhookLogs::class)->name('webhooks.logs')->middleware('can:manage-settings');
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

        // Email Templates (System)
        Route::get('/admin/email-templates', \App\Livewire\Admin\EmailTemplates\Index::class)->name('admin.email-templates.index');
        Route::get('/admin/email-templates/create', \App\Livewire\Admin\EmailTemplates\Create::class)->name('admin.email-templates.create');
        Route::get('/admin/email-templates/{template}', \App\Livewire\Admin\EmailTemplates\Edit::class)->name('admin.email-templates.edit');

        // Email Logs (Tracking)
        Route::get('/admin/email-logs', [\App\Http\Controllers\Admin\EmailLogController::class, 'index'])->name('admin.email-logs.index');
        Route::get('/admin/email-logs/{log}', [\App\Http\Controllers\Admin\EmailLogController::class, 'show'])->name('admin.email-logs.show');
    });

    // Logout and Exit Impersonation (Universal)
    Route::get('/admin/impersonate/exit', [\App\Http\Controllers\Admin\ImpersonationController::class, 'exit'])->name('admin.impersonate.exit');

    // Agent Console (Agents, Managers, Admins)
    Route::get('/chat', \App\Livewire\Chat\ChatDashboard::class)->name('chat')->middleware('can:chat-access');

    // Conversation Locks (Session Auth for Web)
    Route::post('/api/v1/conversations/{conversation}/lock', [\App\Http\Controllers\ConversationController::class, 'lock']);
    Route::post('/api/v1/conversations/{conversation}/unlock', [\App\Http\Controllers\ConversationController::class, 'unlock']);
    Route::post('/api/v1/conversations/{conversation}/heartbeat', [\App\Http\Controllers\ConversationController::class, 'heartbeat']);
    Route::post('/api/v1/conversations/{conversation}/takeover', [\App\Http\Controllers\ConversationController::class, 'forceTakeOver']);

    // CRM (Managers, Admins)
    Route::get('/contacts', function () {
        return view('contacts.index');
    })->name('contacts.index')->middleware('can:manage-contacts');

    // Marketing & Funnels (Managers, Admins) - Requires 'campaigns' feature
    Route::get('/campaigns', \App\Livewire\Campaigns\CampaignList::class)->name('campaigns.index')->middleware(['can:manage-campaigns', 'plan_feature:campaigns']);

    Route::get('/campaigns/create', \App\Livewire\Campaigns\Wizard::class)->name('campaigns.create')->middleware(['can:manage-campaigns', 'plan_feature:campaigns']);

    Route::get('/campaigns/{campaignId}', \App\Livewire\Campaigns\Show::class)->name('campaigns.show')->middleware(['can:manage-campaigns', 'plan_feature:campaigns']);

    Route::get('/campaigns/{campaign}/live', \App\Livewire\Campaigns\Dashboard::class)->name('campaigns.live')->middleware(['can:manage-campaigns', 'plan_feature:campaigns']);

    Route::get('/templates', function () {
        return view('templates.index');
    })->name('templates.index')->middleware('can:manage-templates');

    // Compliance Modules
    Route::get('/compliance', \App\Livewire\Compliance\ComplianceManager::class)->name('compliance.index')->middleware('can:manage-settings');
    Route::get('/compliance/logs', [\App\Http\Controllers\ComplianceController::class, 'logs'])->name('compliance.logs')->middleware('can:manage-settings');
    Route::get('/compliance/registry', [\App\Http\Controllers\ComplianceController::class, 'registry'])->name('compliance.registry')->middleware('can:manage-settings');

    Route::get('/automations', \App\Livewire\Automations\AutomationList::class)->name('automations.index')->middleware(['can:manage-campaigns', 'plan_feature:automations']);

    Route::get('/automations/builder/{automationId?}', \App\Livewire\Automations\AutomationBuilder::class)->name('automations.builder')->middleware(['can:manage-campaigns', 'plan_feature:automations']);

    // WhatsApp Flows
    Route::get('/flows', \App\Livewire\Flows\FlowManager::class)->name('flows.index')->middleware(['can:manage-campaigns', 'plan_feature:flows']);
    Route::get('/flows/builder/{flowId?}', \App\Livewire\Flows\FlowBuilder::class)->name('flows.builder')->middleware(['can:manage-campaigns', 'plan_feature:flows']);

    Route::get('/analytics', \App\Livewire\Analytics\AnalyticsDashboard::class)->name('analytics')->middleware(['can:manage-settings', 'plan_feature:analytics']);
    Route::get('/analytics/events', \App\Livewire\Analytics\EventDashboard::class)->name('analytics.events')->middleware('can:manage-settings');
    Route::get('/analytics/explorer', \App\Livewire\Analytics\EventExplorer::class)->name('analytics.explorer')->middleware('can:manage-settings');

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
    Route::get('/developer/docs', [\App\Http\Controllers\Developer\ApiDocumentationController::class, 'index'])->name('developer.docs')->middleware('plan_feature:api_access');
    // Commerce
    // Commerce - Requires 'commerce' feature
    Route::get('/commerce', \App\Livewire\Commerce\Dashboard::class)->name('commerce.dashboard')->middleware(['can:manage-campaigns', 'plan_feature:commerce']);
    Route::get('/commerce/orders', \App\Livewire\Commerce\OrderManager::class)->name('commerce.orders')->middleware(['can:manage-campaigns', 'plan_feature:commerce']);
    Route::get('/commerce/products', \App\Livewire\Commerce\ProductManager::class)->name('commerce.products')->middleware(['can:manage-campaigns', 'plan_feature:commerce']);
    Route::get('/commerce/settings', \App\Livewire\Commerce\CommerceSettings::class)->name('commerce.settings')->middleware(['can:manage-settings', 'plan_feature:commerce']);
    Route::get('/integrations/ecommerce', \App\Livewire\Integrations\EcommerceIntegrations::class)->name('integrations.ecommerce')->middleware(['can:manage-settings', 'plan_feature:commerce']);

    // Google Drive Integration
    Route::prefix('integrations/google-drive')->name('integrations.google-drive.')->group(function () {
        Route::get('/redirect', [GoogleDriveController::class, 'redirect'])->name('redirect');
        Route::get('/callback', [GoogleDriveController::class, 'callback'])->name('callback');
        Route::post('/disconnect', [GoogleDriveController::class, 'disconnect'])->name('disconnect');
    });

    // Backup & Restore
    Route::get('/backups', [\App\Http\Controllers\Backup\BackupController::class, 'index'])->name('backups.index');
    Route::post('/backups', [\App\Http\Controllers\Backup\BackupController::class, 'store'])->name('backups.store');
    Route::get('/backups/{id}/download', [\App\Http\Controllers\Backup\BackupController::class, 'download'])->name('backups.download');
    Route::post('/backups/{id}/restore', [\App\Http\Controllers\Backup\RestoreController::class, 'restore'])->name('backups.restore');
    // Identity Management
    Route::delete('/identities/{identity}', [\App\Http\Controllers\UserIdentityController::class, 'destroy'])->name('identities.destroy');

});

// Embed Routes (Publicly accessible but Token protected internally)
Route::get('/embed/chat', [\App\Http\Controllers\EmbedController::class, 'show'])->name('embed.chat');
