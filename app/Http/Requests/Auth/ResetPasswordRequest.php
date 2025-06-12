<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseFormRequest;

final class ResetPasswordRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'token' => 'required|string',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|confirmed|min:8',
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => __('validation.token.required'),
            'email.required' => __('validation.email.required'),
            'email.email' => __('validation.email.email'),
            'email.exists' => __('validation.email.not_found'),
            'password.required' => __('validation.password.required'),
            'password.string' => __('validation.password.string'),
            'password.confirmed' => __('validation.password.confirmed'),
            'password.min' => __('validation.password.min'),
        ];
    }
}
