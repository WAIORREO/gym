<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriptionRequest extends FormRequest
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
            'package_id' => 'required|exists:packages,id',
            'user_id' => 'required|exists:users,id',
            'interval' => 'nullable|numeric|min:1',
            'status' => 'nullable|in:active,inactive,expired,suspended,completed',
            'total_sessions' => 'nullable|integer|min:0',
            'remaining_sessions' => 'nullable|integer|min:0',
            'expires_at' => 'nullable|date',
        ];
    }
}
