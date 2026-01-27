<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use App\Enums\EmailUseCase;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'type' => EmailUseCase::OTP,
                'name' => 'User OTP Login',
                'slug' => 'user-otp-login',
                'subject' => 'Your Login Code: {{ code }}',
                'content_html' => '<div style="font-family: sans-serif;"><h2>Login Verification</h2><p>Hello {{ name }},</p><p>Your login code is: <strong style="font-size: 24px;">{{ code }}</strong></p><p>This code expires in {{ expiry }}.</p><p>If you did not request this, please ignore this email.</p></div>',
                'content_text' => "Hello {{ name }},\nYour login code is {{ code }}.\nThis expires in {{ expiry }}.",
                'variable_schema' => ['name', 'code', 'expiry'],
                'is_locked' => true,
                'description' => 'Sent when a user requests a login OTP.',
            ],
            [
                'type' => EmailUseCase::ALERT,
                'name' => 'System Health Alert',
                'slug' => 'system-health-alert',
                'subject' => 'Alert: {{ service_name }} Issue',
                'content_html' => '<h1 style="color: red;">System Alert</h1><p>Service: <strong>{{ service_name }}</strong></p><p>Status: {{ status }}</p><p>Time: {{ time }}</p><pre>{{ details }}</pre>',
                'content_text' => "System Alert\nService: {{ service_name }}\nStatus: {{ status }}\nTime: {{ time }}\n\n{{ details }}",
                'variable_schema' => ['service_name', 'status', 'time', 'details'],
                'is_locked' => true,
                'description' => 'Critical alerts for system administrators.',
            ],
            [
                'type' => EmailUseCase::MARKETING,
                'name' => 'Welcome Email',
                'slug' => 'user-welcome',
                'subject' => 'Welcome to Platform, {{ name }}!',
                'content_html' => '<h1>Welcome {{ name }}!</h1><p>We are glad to have you on board.</p>',
                'content_text' => "Welcome {{ name }}!\nWe are glad to have you on board.",
                'variable_schema' => ['name'],
                'is_locked' => false,
                'description' => 'Welcome email for new signups.',
            ],
            [
                'type' => EmailUseCase::ALERT,
                'name' => 'WhatsApp Health Alert',
                'slug' => 'whatsapp-health-alert',
                'subject' => '🚨 WhatsApp Health Issue: {{ team_name }}',
                'content_html' => '<div style="font-family: sans-serif;"><h2>WhatsApp Alert</h2><p>Team: {{ team_name }}</p><p><strong>Message:</strong> {{ message }}</p><p>Severity: {{ severity }}</p><p><a href="{{ dashboard_url }}">Go to Dashboard</a></p></div>',
                'content_text' => "WhatsApp Alert\nTeam: {{ team_name }}\nMessage: {{ message }}\nSeverity: {{ severity }}\nDashboard: {{ dashboard_url }}",
                'variable_schema' => ['team_name', 'message', 'severity', 'dashboard_url'],
                'is_locked' => true,
                'description' => 'Sent when a team\'s WhatsApp health degrades.',
            ]
        ];

        foreach ($templates as $tmpl) {
            EmailTemplate::updateOrCreate(
                ['slug' => $tmpl['slug']],
                $tmpl
            );
        }
    }
}
