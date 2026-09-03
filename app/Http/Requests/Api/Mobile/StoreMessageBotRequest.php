<?php

namespace App\Http\Requests\Api\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageBotRequest extends FormRequest
{
    public function authorize()
    {
        $team = $this->user()->currentTeam;
        if (! $team) {
            return false;
        }

        // If user is explicitly assigned the 'agent' role on the team, deny creation permission
        $member = $team->users->where('id', $this->user()->id)->first();
        if ($member?->membership?->role === 'agent') {
            return false;
        }

        return $this->user()->hasTeamPermission($team, 'manage-workflows');
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
