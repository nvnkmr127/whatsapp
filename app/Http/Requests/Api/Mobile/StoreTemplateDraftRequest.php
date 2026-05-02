<?php

namespace App\Http\Requests\Api\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class StoreTemplateDraftRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->hasTeamPermission($this->user()->currentTeam, 'manage-templates');
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255|regex:/^[a-z0-9_]+$/',
            'category' => 'required|string|in:MARKETING,UTILITY,AUTHENTICATION',
            'language' => 'required|string|max:10',
            'components' => 'required|array',
            'components.*.type' => 'required|string|in:HEADER,BODY,FOOTER,BUTTONS',
        ];
    }

    public function messages()
    {
        return [
            'name.regex' => 'The template name must only contain lowercase letters, numbers, and underscores.',
        ];
    }
}
