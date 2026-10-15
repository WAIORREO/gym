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
            'status' => 'sometimes|required|in:active,inactive,expired,suspended,cancelled,completed',
            'total_sessions' => 'nullable|integer|min:0|max:100000',
            'remaining_sessions' => 'nullable|integer|min:0|max:100000',
            'sessions_used' => 'nullable|integer|min:0|max:100000',
            'starts_at' => 'nullable|date',
            'expires_at' => 'sometimes|required|date',
            'interval' => 'sometimes|required|integer|min:1|max:120',
            'user_id' => 'sometimes|required|integer|exists:users,id',
            'package_id' => 'sometimes|required|integer|exists:packages,id'
        ];
    }
}
