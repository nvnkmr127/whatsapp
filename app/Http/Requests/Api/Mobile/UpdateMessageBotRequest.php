<?php

namespace App\Http\Requests\Api\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMessageBotRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->hasTeamPermission($this->user()->currentTeam, 'manage-workflows');
    }

    public function rules()
    {
        return [
            'name' => 'sometimes|string|max:255',
            'trigger' => 'sometimes|array',
            'reply_text' => 'sometimes|string',
            'is_bot_active' => 'sometimes|boolean',
        ];
    }
}
