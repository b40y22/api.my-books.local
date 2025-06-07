<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseFormRequest;

final class RegisterRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'firstname' => 'required|string',
            'lastname' => 'nullable|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|confirmed|min:8',
        ];
    }

    public function messages(): array
    {
        return [
            'firstname.required' => __('validation.firstname.required'),
            'firstname.string' => __('validation.firstname.string'),
            'lastname.string' => __('validation.lastname.string'),
            'email.required' => __('validation.email.required'),
            'email.email' => __('validation.email.email'),
            'email.unique' => __('validation.email.unique'),
            'password.required' => __('validation.password.required'),
            'password.string' => __('validation.password.string'),
            'password.confirmed' => __('validation.password.confirmed'),
            'password.min' => __('validation.password.min'),
        ];
    }

    public function getAcceptLanguage(): ?string
    {
        return $this->header('Accept-Language');
    }
}
