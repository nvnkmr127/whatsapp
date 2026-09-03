<?php

namespace App\Http\Requests\Api\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMessageBotRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->hasTeamPermission($this->user()->currentTeam, 'manage-workflows');
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active') && ! $this->has('is_bot_active')) {
            $this->merge(['is_bot_active' => $this->boolean('is_active')]);
        }
        if (is_string($this->input('trigger'))) {
            $decoded = json_decode($this->input('trigger'), true);
            if (is_array($decoded)) {
                $this->merge(['trigger' => $decoded]);
            }
        }
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
