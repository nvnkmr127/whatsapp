<?php

namespace App\Http\Requests\Api\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTemplateDraftRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->hasTeamPermission($this->user()->currentTeam, 'manage-templates');
    }

    public function rules()
    {
        return [
            'category' => 'sometimes|string|in:MARKETING,UTILITY,AUTHENTICATION',
            'components' => 'sometimes|array',
            'components.*.type' => 'required_with:components|string|in:HEADER,BODY,FOOTER,BUTTONS',
        ];
    }
}
