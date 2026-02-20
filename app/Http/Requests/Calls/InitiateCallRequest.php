<?php

namespace App\Http\Requests\Calls;

use Illuminate\Foundation\Http\FormRequest;

class InitiateCallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->currentTeam !== null;
    }

    public function rules(): array
    {
        return [
            'phone_number' => 'required|string|regex:/^\+?[0-9]{10,15}$/',
            'options' => 'sometimes|array',
            'options.force_new_conversation' => 'sometimes|boolean',
            'options.metadata' => 'sometimes|array',
        ];
    }

    public function messages(): array
    {
        return [
            'phone_number.regex' => 'The phone number format is invalid. Please use E.164 format (e.g., +1234567890).',
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->has('phone_number')) {
            $this->merge(['phone_number' => preg_replace('/[^0-9+]/', '', $this->phone_number)]);
        }
    }
}
