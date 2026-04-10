<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class SettingsHub extends Component
{
    public $search = '';

    public function getSettingsCardsProperty()
    {
        $cards = [
            [
                'title' => 'WhatsApp API',
                'description' => 'Connect your Meta Business account, manage phone numbers, and profile.',
                'route' => 'teams.whatsapp_config',
                'icon' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z',
                'color' => 'green',
                'privelege' => 'manage-settings',
            ],
            [
                'title' => 'AI Brain',
                'description' => 'Train your AI, manage knowledge base documents, and configure auto-replies.',
                'route' => 'knowledge-base.index',
                'icon' => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z',
                'color' => 'blue',
                'privelege' => 'manage-settings',
                'feature' => 'ai',
            ],
            [
                'title' => 'Chat Routing',
                'description' => 'Configure how chats are assigned to agents and teams.',
                'route' => 'settings.chat-routing',
                'icon' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
                'color' => 'purple',
                'privelege' => 'manage-settings',
            ],
            [
                'title' => 'Quick Replies',
                'description' => 'Manage canned responses for frequently asked questions.',
                'route' => 'settings.canned-messages',
                'icon' => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z',
                'color' => 'amber',
                'privelege' => 'manage-settings',
            ],
            [
                'title' => 'Taxonomy',
                'description' => 'Manage contact categories, chat tags, and conversation labels.',
                'route' => 'settings.categories',
                'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
                'color' => 'rose',
                'privelege' => 'manage-settings',
            ],
            [
                'title' => 'Environment',
                'description' => 'Global system settings, email configuration, and core preferences.',
                'route' => 'settings.system',
                'icon' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z',
                'color' => 'slate',
                'privelege' => 'manage-settings',
            ],
            [
                'title' => 'Security',
                'description' => 'Compliance registry, audit logs, and data security policies.',
                'route' => 'compliance.index',
                'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                'color' => 'orange',
                'privelege' => 'manage-settings',
            ],
            [
                'title' => 'Backups',
                'description' => 'Manage system snapshots, automated backups, and data restoration.',
                'route' => 'backups.index',
                'icon' => 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4',
                'color' => 'cyan',
                'privelege' => 'manage-settings',
            ],
            [
                'title' => 'Mobile App',
                'description' => 'Configure and sync your mobile devices for on-the-go workspace management.',
                'route' => 'settings.mobile-app',
                'icon' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z',
                'color' => 'indigo',
                'privelege' => 'manage-settings',
            ],
            [
                'title' => 'Dev Tools',
                'description' => 'API Tokens, Webhook management, and technical documentation.',
                'route' => 'developer.overview',
                'icon' => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4',
                'color' => 'slate',
                'privelege' => ['manage-settings', 'plan_feature:api_access'],
            ],
        ];

        if (Auth::user()->isSuperAdmin()) {
            $cards[] = [
                'title' => 'Lifecycle',
                'description' => 'Map WhatsApp templates to onboarding stages and inactivity reminders.',
                'route' => 'settings.onboarding-automation',
                'icon' => 'M13 10V3L4 14h7v7l9-11h-7z',
                'color' => 'indigo',
                'privelege' => 'manage-settings',
            ];
        }

        if ($this->search) {
            $cards = array_filter($cards, function ($card) {
                return str_contains(strtolower($card['title']), strtolower($this->search)) ||
                       str_contains(strtolower($card['description']), strtolower($this->search));
            });
        }

        return $cards;
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.settings.settings-hub');
    }
}
