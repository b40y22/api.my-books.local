<?php

return [
    // Email validation
    'email' => [
        'required' => 'Email address is required.',
        'email' => 'Please provide a valid email address.',
        'unique' => 'This email address is already registered.',
        'exists' => 'This email address is not registered.',
    ],

    // Password validation
    'password' => [
        'required' => 'Password is required.',
        'string' => 'Password must be a valid string.',
        'confirmed' => 'Password confirmation does not match.',
        'min' => 'Password must be at least :min characters.',
        'min_login' => 'Password is required.',
    ],

    // Name validation
    'firstname' => [
        'required' => 'First name is required.',
        'string' => 'First name must be a valid string.',
    ],

    'lastname' => [
        'string' => 'Last name must be a valid string.',
    ],

    // Remember validation
    'remember' => [
        'boolean' => 'Remember me must be true or false.',
    ],
];
