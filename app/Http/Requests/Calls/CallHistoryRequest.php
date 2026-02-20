<?php

namespace App\Http\Requests\Calls;

use Illuminate\Foundation\Http\FormRequest;

class CallHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->currentTeam !== null;
    }

    public function rules(): array
    {
        return [
            'direction' => 'sometimes|in:inbound,outbound',
            'status' => 'sometimes|string',
            'contact_id' => 'sometimes|integer|exists:contacts,id',
            'from_date' => 'sometimes|date',
            'to_date' => 'sometimes|date|after_or_equal:from_date',
            'sort_by' => 'sometimes|in:created_at,initiated_at,answered_at,ended_at,duration_seconds,cost_amount,status,direction',
            'sort_order' => 'sometimes|in:asc,desc',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ];
    }
}
