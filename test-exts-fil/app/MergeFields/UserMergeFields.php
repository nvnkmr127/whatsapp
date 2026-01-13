<?php

namespace App\MergeFields;

use App\Models\User;

class UserMergeFields
{
    public function name(): string
    {
        return 'user-group';
    }

    public function templates(): array
    {
        return [
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
                'name' => 'First Name',
                'key'  => '{first_name}',
            ],
            [
                'name' => 'Last Name',
                'key'  => '{last_name}',
            ],
            [
                'name' => 'User Email',
                'key'  => '{user_email}',
            ],
        ];
    }

    public function format(array $context): array
    {
        if (empty($context['userId']) || is_null($context['userId'])) {
            return [];
        }

        $user = User::findOrFail($context['userId']);

        return [
            '{first_name}' => $user->firstname,
            '{last_name}'  => $user->lastname,
            '{user_email}' => $user->email,
        ];
    }
}
