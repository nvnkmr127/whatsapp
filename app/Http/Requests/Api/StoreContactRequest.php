<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->currentTeam !== null;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $rawPhone = $this->input('phone_number') ?? $this->input('phone') ?? $this->input('to');
        if ($rawPhone) {
            // Remove common phone formatting (spaces, hyphens, parentheses)
            $cleaned = preg_replace('/[\s\-\(\)]+/', '', (string) $rawPhone);
            $this->merge(['phone_number' => $cleaned]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'phone_number' => ['required', 'string', 'regex:/^\+?[1-9]\d{1,14}$/'],
            'name' => 'nullable|string',
            'email' => 'nullable|email',
            'custom_attributes' => 'nullable|array',
            'custom_attributes.email' => 'nullable|email|max:255',
            'custom_attributes.company' => 'nullable|string|max:255',
            'opt_in' => 'nullable|boolean',
            'opt_in_source' => 'nullable|string',
            'opt_in_notes' => 'nullable|string',
            'opt_in_proof_url' => 'nullable|url',
        ];
    }
}
