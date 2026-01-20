<?php

namespace App\View\Components\Layouts;

use Illuminate\View\Component;
use Illuminate\Support\Facades\Route;

class Sidebar extends Component
{
    public function render()
    {
        return view('components.layouts.sidebar', [
            'menuGroups' => [
                'Core' => $this->coreLinks(),
                'Engagement' => $this->engagementLinks(),
                'Commerce' => $this->commerceLinks(),
                'Intelligence' => $this->intelligenceLinks(),
                'Compliance' => $this->complianceLinks(),
                'Settings & Dev' => $this->settingsLinks(),
            ]
        ]);
    }

    protected function coreLinks()
    {
        return [
            [
                'route' => 'dashboard',
                'label' => 'Overview',
                'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'
            ],
            [
                'route' => 'teams.whatsapp_config',
                'label' => 'WhatsApp API',
                'icon' => 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1',
                'children' => [
                    ['route' => 'teams.whatsapp_config', 'label' => 'Configurations'],
                    ['route' => 'teams.whatsapp_opt_in', 'label' => 'Opt-In Manager'],
                    ['route' => 'teams.inbox_settings', 'label' => 'Inbox Settings'],
                    ['route' => 'settings.canned-messages', 'label' => 'Canned Responses'],
                ]
            ],
            [
                'route' => 'chat',
                'label' => 'Shared Inbox',
                'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
                'can' => 'chat-access'
            ],
        ];
    }

    protected function engagementLinks()
    {
        return [
            [
                'route' => 'campaigns.index',
                'label' => 'Broadcasting',
                'icon' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z'
            ],
            [
                'route' => 'contacts.index',
                'label' => 'Contacts',
                'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'
            ],
            [
                'route' => 'templates.index',
                'label' => 'Templates',
                'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'
            ],
            [
                'route' => 'automations.index',
                'label' => 'Bot Manager',
                'icon' => 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z',
                'children' => [
                    ['route' => 'automations.index', 'label' => 'Automations'],
                    ['route' => 'automations.builder', 'label' => 'Bot Builder'],
                ]
            ],
        ];
    }

    protected function commerceLinks()
    {
        return [
            [
                'route' => 'commerce.dashboard',
                'label' => 'Overview',
                'icon' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',
                'children' => [
                    ['route' => 'commerce.dashboard', 'label' => 'Commerce Dashboard'],
                    ['route' => 'commerce.products', 'label' => 'Products'],
                    ['route' => 'commerce.orders', 'label' => 'Orders'],
                    ['route' => 'integrations.ecommerce', 'label' => 'Integrations'],
                    ['route' => 'commerce.settings', 'label' => 'Settings'],
                ]
            ],
        ];
    }

    protected function intelligenceLinks()
    {
        return [
            [
                'route' => 'analytics',
                'label' => 'Analytics',
                'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
                'children' => [
                    ['route' => 'analytics', 'label' => 'Dashboard'],
                    ['route' => 'analytics.events', 'label' => 'Customer Events'],
                ]
            ],
            [
                'route' => 'flows.index',
                'label' => 'Smart Flows',
                'icon' => 'M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z',
                'children' => [
                    ['route' => 'flows.index', 'label' => 'Flow Builder'],
                    ['route' => 'flows.builder', 'label' => 'Visual Canvas'],
                ]
            ],
            [
                'route' => 'knowledge-base.index',
                'label' => 'Knowledge Base',
                'icon' => 'M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25',
                'children' => [
                    ['route' => 'knowledge-base.index', 'label' => 'AI Brain'],
                    ['route' => 'settings.ai', 'label' => 'AI Config'],
                ]
            ],
        ];
    }

    protected function complianceLinks()
    {
        return [
            [
                'route' => 'compliance.index',
                'label' => 'Compliance',
                'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                'children' => [
                    ['route' => 'compliance.index', 'label' => 'Overview'],
                    ['route' => 'compliance.registry', 'label' => 'Registry'],
                    ['route' => 'compliance.logs', 'label' => 'Audit Logs'],
                ]
            ],
        ];
    }

    protected function settingsLinks()
    {
        return [
            [
                'route' => 'settings.system',
                'label' => 'System Settings',
                'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 001.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'
            ],
            [
                'route' => 'teams.members',
                'label' => 'Team Members',
                'icon' => 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z'
            ],
            [
                'route' => 'billing',
                'label' => 'Billing & Usage',
                'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'
            ],
            [
                'route' => 'activity.index',
                'label' => 'Activity Log',
                'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'
            ],
            [
                'route' => 'backups.index',
                'label' => 'Backup & Restore',
                'icon' => 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4',
                'can' => 'manage-settings'
            ],
            [
                'route' => 'developer.overview',
                'label' => 'Developer',
                'icon' => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4',
                'children' => [
                    ['route' => 'developer.overview', 'label' => 'Overview'],
                    ['route' => 'developer.webhooks', 'label' => 'Webhooks'],
                    ['route' => 'webhooks.logs', 'label' => 'Webhook Logs'],
                    ['route' => 'developer.api-tokens', 'label' => 'API Keys'],
                    ['route' => 'developer.docs', 'label' => 'API Docs'],
                    ['route' => 'webhook-sources.index', 'label' => 'Inbound Sources'],
                ]
            ],
            [
                'route' => 'settings.categories',
                'label' => 'Categories',
                'icon' => 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z'
            ],
        ];
    }
}
