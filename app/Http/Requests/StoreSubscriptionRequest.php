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
            'interval' => 'sometimes|required|integer|min:1|max:120',
            'status' => 'sometimes|required|in:active,inactive,expired,suspended,completed',
            'total_sessions' => 'nullable|integer|min:0|max:100000',
            'remaining_sessions' => 'nullable|integer|min:0|max:100000',
            'starts_at' => 'nullable|date',
            'expires_at' => 'sometimes|required|date',
        ];
    }
}
