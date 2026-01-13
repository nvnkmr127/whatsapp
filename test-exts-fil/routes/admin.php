<?php

/*
Project         :   WhatsApp Marketing & Automation Platform with Bots, Chats, Bulk Sender & AI
@package        :   Laravel
Laravel Version :   11.41.3
PHP Version     :   8.2.18
Created Date    :   14-01-2025
Copyright       :   Corbital Technologies LLP
Author          :   CORBITALTECH™
Author URL      :   https://codecanyon.net/user/corbitaltech
Support         :   contact@corbitaltech.dev
License         :   Licensed under Codecanyon Licence
*/

use App\Http\Controllers\WhatsApp\ChatController;
use App\Http\Controllers\WhatsApp\WhatsAppWebhookController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\SanitizeInputs;
use App\Livewire\Admin\Bot\MessageBotCreator;
use App\Livewire\Admin\Bot\MessageBotList;
use App\Livewire\Admin\Bot\TemplateBotCreator;
use App\Livewire\Admin\Bot\TemplateBotList;
use App\Livewire\Admin\Campaign\CampaignCreator;
use App\Livewire\Admin\Campaign\CampaignDetails;
use App\Livewire\Admin\Campaign\CampaignList;
use App\Livewire\Admin\Campaign\CsvCampaign;
use App\Livewire\Admin\Chat\ManageAiPrompt;
use App\Livewire\Admin\Chat\ManageCannedReply;
use App\Livewire\Admin\Contact\ContactCreator;
use App\Livewire\Admin\Contact\ContactList;
use App\Livewire\Admin\Contact\ImportContact;
use App\Livewire\Admin\Contact\ManageSource;
use App\Livewire\Admin\Contact\ManageStatus;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Miscellaneous\ActivityLogDetails;
use App\Livewire\Admin\Miscellaneous\ActivityLogList;
use App\Livewire\Admin\Miscellaneous\EmailTemplateList;
use App\Livewire\Admin\Miscellaneous\EmailTemplateSave;
use App\Livewire\Admin\Miscellaneous\RoleCreator;
use App\Livewire\Admin\Miscellaneous\RoleList;
use App\Livewire\Admin\Profile\ProfileManager;
use App\Livewire\Admin\Settings\Language\LanguageManager;
use App\Livewire\Admin\Settings\Language\TranslationManager;
use App\Livewire\Admin\Template\TemplateList;
use App\Livewire\Admin\User\UserCreator;
use App\Livewire\Admin\User\UserDetails;
use App\Livewire\Admin\User\UserList;
use App\Livewire\Admin\Waba\ConnectWaba;
use App\Livewire\Admin\Waba\DisconnectWaba;
use App\Livewire\LogViewer;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', AdminMiddleware::class, SanitizeInputs::class, EnsureEmailIsVerified::class])

    ->group(function () {
        // Dashboard
        Route::get('/', Dashboard::class)->name('dashboard');

        // Contacts
        Route::get('contacts', ContactList::class)->name('contacts.list');
        Route::get('contacts/contact/{contactId?}', ContactCreator::class)->name('contacts.save');
        Route::get('status', ManageStatus::class)->name('status');
        Route::get('source', ManageSource::class)->name('source');
        Route::get('importcontact', ImportContact::class)->name('contacts.imports');

        // WhatsApp API
        Route::get('connect', ConnectWaba::class)->name('connect');
        Route::get('waba', DisconnectWaba::class)->name('waba');

        // Templates & Bots
        Route::get('template', TemplateList::class)->name('template.list');
        Route::get('message-bot', MessageBotList::class)->name('messagebot.list');
        Route::get('message-bot/bot/{messagebotId?}', MessageBotCreator::class)->name('messagebot.create');
        Route::get('template-bot', TemplateBotList::class)->name('templatebot.list');
        Route::get('template-bot/bot/{templatebotId?}', TemplateBotCreator::class)->name('templatebot.create');

        // Campaigns
        Route::get('campaigns', CampaignList::class)->name('campaigns.list');
        Route::get('campaigns/campaign/{campaignId?}', CampaignCreator::class)->name('campaigns.save');
        Route::get('campaigns/campaign/details/{campaignId?}', CampaignDetails::class)->name('campaigns.details');
        Route::get('csvcampaign', CsvCampaign::class)->name('csvcampaign');

        // Chat
        Route::get('ai-prompt', ManageAiPrompt::class)->name('ai-prompt');
        Route::get('canned-reply', ManageCannedReply::class)->name('canned-reply');
        Route::get('chat', [ChatController::class, 'index'])->name('chat');
        Route::get('chat_messages/{chatId?}/{lastMessageId?}', [ChatController::class, 'messagesGet'])->name('chat_messages');
        Route::post('send-message', [WhatsAppWebhookController::class, 'send_message'])->name('send_message');
        Route::post('remove-message/{messageId?}', [ChatController::class, 'removeMessage'])->name('remove_message');
        Route::post('remove-chat/{chatId?}', [ChatController::class, 'removeChat'])->name('remove_chat');
        Route::post('assign-agent/{chatId?}', [ChatController::class, 'assignSupportAgent'])->name('assign-agent');
        Route::get('assign-agent-layout/{chatId?}', [ChatController::class, 'getSupportAgentView'])->name('assign-agent-layout');
        Route::post('ai-response', [ChatController::class, 'processAiResponse'])->name('ai_response');
        Route::post('user-information', [ChatController::class, 'userInformation'])->name('user_information');
        Route::post('load-mergefields/{chatType}', [ChatController::class, 'loadMergeFields'])->name('load_mergefields');

        // Miscellaneous
        Route::get('activity-log', ActivityLogList::class)->name('activity-log.list');
        Route::get('activity-log/{logId?}', ActivityLogDetails::class)->name('activity-log.details');
        Route::get('roles', RoleList::class)->name('roles.list');
        Route::get('roles/role/{roleId?}', RoleCreator::class)->name('roles.save');

        // Users & Profile
        Route::get('users', UserList::class)->name('users.list');
        Route::get('users/user/{userId?}', UserCreator::class)->name('users.save');
        Route::get('users/{userId?}', UserDetails::class)->name('users.details');
        Route::get('/profile', ProfileManager::class)->name('profile.edit');

        // Language
        Route::get('/languages', LanguageManager::class)->name('languages');
        Route::get('/languages/{code}/translations', TranslationManager::class)->name('languages.translations');

        // Email Templates
        Route::get('emails', EmailTemplateList::class)->name('emails');
        Route::get('emails/{id?}', EmailTemplateSave::class)->name('emails.save');

        Route::get('/logs', LogViewer::class)->name('logs.index');
        Route::get('/ajax/chart-data', function () {
            $timeRange = request()->get('timeRange', 'today');

            // Create a new instance of the Dashboard component
            $dashboard = new \App\Livewire\Admin\Dashboard;

            // Set the time range and load the data
            $dashboard->timeRange = $timeRange;
            $dashboard->loadMessageStats();

            // Return the chart data as JSON
            return response()->json($dashboard->chartData);
        })->name('admin.ajax.chart-data');
    });
