<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class OTPVerifyRequest extends FormRequest
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
     */
    public function rules(): array
    {
        $rules = [
            'identifier' => 'required|string',
            'type' => 'required|in:email,phone',
            'code' => 'required|string|size:6',
        ];

        return $rules;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        if ($this->has('identifier')) {
            if ($this->input('type') === 'phone') {
                $this->merge(['identifier' => preg_replace('/[^0-9+]/', '', $this->identifier)]);
            } else {
                $this->merge(['identifier' => strtolower(trim($this->identifier))]);
            }
        }
    }
}
