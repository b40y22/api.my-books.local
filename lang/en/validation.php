<?php

return [
    'duplicate' => 'The :attribute field must be a valid email address.',
    'firstname' => [
        'required' => 'The firstname field is required.',
        'string' => 'The firstname field must be a string.',
    ],
    'lastname' => [
        'string' => 'The lastname field must be a string.',
    ],
    'email' => [
        'required' => 'The email field is required.',
        'email' => 'The email field must be a valid email address.',
        'unique' => 'This email is already registered.',
    ],
    'password' => [
        'required' => 'The password field is required.',
        'string' => 'The password field must be a string.',
        'confirmed' => 'The password confirmation does not match.',
        'min' => 'The password must be at least 8 characters.',
    ],
];
