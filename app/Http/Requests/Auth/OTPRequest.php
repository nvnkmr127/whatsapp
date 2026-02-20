<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class OTPRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $type = $this->input('type', 'email');

        return [
            'identifier' => [
                'required',
                'string',
                $type === 'email' ? 'email' : 'regex:/^\+?[0-9]{10,15}$/',
            ],
            'type' => 'required|in:email,phone',
            'recaptcha_token' => 'sometimes|string', // Optional bot protection hook
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'identifier.regex' => 'Please enter a valid phone number with country code (e.g., +1234567890).',
            'identifier.email' => 'Please enter a valid email address.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        if ($this->has('identifier')) {
            // Normalize phone number if it's a phone type
            if ($this->input('type') === 'phone') {
                $phone = preg_replace('/[^0-9+]/', '', $this->identifier);
                if (!str_starts_with($phone, '+')) {
                    // Default to no prefix for now or look for country code param
                }
                $this->merge(['identifier' => $phone]);
            } else {
                $this->merge(['identifier' => strtolower(trim($this->identifier))]);
            }
        }
    }
}
