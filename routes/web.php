<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dev/login/{email}', [\App\Http\Controllers\DevController::class, 'loginAs'])->name('dev.login');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/webhook-workflows', \App\Livewire\Webhooks\WorkflowList::class)->name('webhooks.index');
    Route::get('/webhook-workflows/{workflowId}/report', \App\Livewire\Webhooks\WebhookReport::class)->name('webhooks.report');

    // WhatsApp Config (Admins Only)
    Route::get('/whatsapp/setup', function () {
        return view('teams.whatsapp-config');
    })->name('teams.whatsapp_config')->middleware('can:manage-settings');

    Route::get('/whatsapp/inbox', function () {
        return view('teams.inbox-settings');
    })->name('teams.inbox_settings')->middleware('can:manage-settings');

    Route::get('/whatsapp/opt-in', \App\Livewire\Teams\OptInManagement::class)->name('teams.whatsapp_opt_in')->middleware('can:manage-settings');

    Route::post('/whatsapp/onboard/exchange', [\App\Http\Controllers\WhatsAppOnboardingController::class, 'exchangeToken'])
        ->name('whatsapp.onboard.exchange')
        ->middleware('can:manage-settings');

    Route::get('/team/members', \App\Livewire\Teams\MembersManager::class)->name('teams.members');

    // Super Admin Routes
    Route::middleware([\App\Http\Middleware\EnsureUserIsSuperAdmin::class])->group(function () {
        Route::get('/admin', [\App\Http\Controllers\SuperAdminController::class, 'dashboard'])->name('admin.dashboard');
        Route::get('/admin/tenants/create', [\App\Http\Controllers\SuperAdminController::class, 'create'])->name('admin.tenants.create');
        Route::post('/admin/tenants', [\App\Http\Controllers\SuperAdminController::class, 'store'])->name('admin.tenants.store');
    });

    // Agent Console (Agents, Managers, Admins)
    Route::get('/chat', \App\Livewire\Chat\ChatDashboard::class)->name('chat')->middleware('can:chat-access');

    // CRM (Managers, Admins)
    Route::get('/contacts', function () {
        return view('contacts.index');
    })->name('contacts.index')->middleware('can:manage-contacts');

    // Marketing & Funnels (Managers, Admins)
    Route::get('/campaigns', \App\Livewire\Campaigns\CampaignList::class)->name('campaigns.index')->middleware('can:manage-campaigns');

    Route::get('/campaigns/create', \App\Livewire\Campaigns\Wizard::class)->name('campaigns.create')->middleware('can:manage-campaigns');

    Route::get('/campaigns/{campaignId}', \App\Livewire\Campaigns\Show::class)->name('campaigns.show')->middleware('can:manage-campaigns');

    Route::get('/templates', function () {
        return view('templates.index');
    })->name('templates.index')->middleware('can:manage-templates');

    // Compliance Modules
    Route::get('/compliance/logs', [\App\Http\Controllers\ComplianceController::class, 'logs'])->name('compliance.logs')->middleware('can:manage-settings');
    Route::get('/compliance/registry', [\App\Http\Controllers\ComplianceController::class, 'registry'])->name('compliance.registry')->middleware('can:manage-settings');

    Route::get('/automations', \App\Livewire\Automations\AutomationList::class)->name('automations.index')->middleware('can:manage-campaigns');

    Route::get('/webhooks', \App\Livewire\Webhooks\WebhookLogs::class)->name('webhooks.logs')->middleware('can:manage-settings');




    Route::get('/automations/builder/{automationId?}', \App\Livewire\Automations\AutomationBuilder::class)->name('automations.builder')->middleware('can:manage-campaigns');

    Route::get('/analytics', \App\Livewire\Analytics\Dashboard::class)->name('analytics')->middleware('can:manage-settings');

    Route::get('/activity', function () {
        return view('activity.index');
    })->name('activity.index')->middleware('can:manage-settings');

    // Developer Portal
    Route::get('/developer', \App\Livewire\Developer\DeveloperOverview::class)->name('developer.overview')->middleware('can:manage-settings');
    Route::get('/developer/webhooks', \App\Livewire\Developer\WebhookManager::class)->name('developer.webhooks')->middleware('can:manage-settings');
    Route::get('/developer/docs', [\App\Http\Controllers\Developer\ApiDocumentationController::class, 'index'])->name('developer.docs');
});

// Embed Routes (Publicly accessible but Token protected internally)
Route::get('/embed/chat', [\App\Http\Controllers\EmbedController::class, 'show'])->name('embed.chat');
