<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubscriptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'status' => 'nullable|in:active,inactive,expired,suspended,cancelled,completed',
            'total_sessions' => 'nullable|integer|min:0',
            'remaining_sessions' => 'nullable|integer|min:0',
            'sessions_used' => 'nullable|integer|min:0',
            'expires_at' => 'nullable|date',
            'interval' => 'nullable|numeric|min:1',
            'package_id' => 'nullable|exists:packages,id'
        ];
    }
}
