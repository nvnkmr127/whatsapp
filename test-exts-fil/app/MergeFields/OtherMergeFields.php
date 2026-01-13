<?php

namespace App\MergeFields;

use Illuminate\Support\Facades\Storage;

class OtherMergeFields
{
    public function name(): string
    {
        return 'other-group';
    }

    public function templates(): array
    {
        return [
            'smtp-test-mail',
            'email-confirmation',
            'welcome-mail',
            'password-reset',
            'new-contact-assigned',
        ];
    }

    public function build(): array
    {
        return [
            [
                'name' => 'Company Name',
                'key'  => '{site_name}',
            ],
            [
                'name' => 'Company Email',
                'key'  => '{company_email}',
            ],
            [
                'name'   => 'Dark Logo',
                'key'    => '{dark_logo}',
                'absent' => [
                    'password-reset',
                ],
            ],
            [
                'name'   => 'Light Logo',
                'key'    => '{light_logo}',
                'absent' => [
                    'password-reset',
                ],
            ],
            [
                'name' => 'Base Url',
                'key'  => '{base_url}',
            ],
        ];
    }

    public function format(): array
    {
        return [
            '{site_name}'     => get_setting('general.site_name', config('app.name')),
            '{company_email}' => get_setting('email.sender_email', env('MAIL_FROM_ADDRESS')),
            '{dark_logo}'     => get_setting('general.site_dark_logo') && Storage::disk('public')->exists(get_setting('general.site_dark_logo'))
            ? asset('storage/' . get_setting('general.site_dark_logo'))
            : asset('/img/dark_logo.png'),
            '{light_logo}' => get_setting('general.site_light_logo') && Storage::disk('public')->exists(get_setting('general.site_light_logo'))
            ? asset('storage/' . get_setting('general.site_light_logo'))
            : asset('/img/light_logo.png'),
            '{base_url}' => url('/'),
        ];
    }
}
