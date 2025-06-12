<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseFormRequest;

final class LoginRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required|string|min:1',
            'remember' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => __('validation.email.required'),
            'email.email' => __('validation.email.email'),
            'password.required' => __('validation.password.required'),
            'password.string' => __('validation.password.string'),
            'password.min' => __('validation.password.min_login'),
        ];
    }
}
