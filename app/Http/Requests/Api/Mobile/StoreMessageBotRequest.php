<?php

namespace App\Http\Requests\Api\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageBotRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->hasTeamPermission($this->user()->currentTeam, 'manage-workflows');
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'trigger' => 'required|array',
            'reply_text' => 'required|string',
            'reply_type' => 'sometimes|string|in:text,media,button',
            'is_bot_active' => 'sometimes|boolean',
        ];
    }
}
