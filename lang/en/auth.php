<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    // Login messages
    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
    'email_not_verified' => 'Please verify your email address before logging in.',
    'login_successful' => 'Login successful.',
    'logout_successful' => 'Logout successful.',
    'logout_failed_no_user' => 'No authenticated user found.',
    'logout_failed_no_token' => 'No active session found.',
    'logout_all_successful' => 'Logged out from all devices successfully.',
    'unauthenticated' => 'Authentication required. Please provide a valid token.',

    // Registration messages
    'registration_successful' => 'Registration successful. Please check your email to verify your account.',
    'user_already_exists' => 'User with this email already exists.',

    // Email verification messages
    'email_verified' => 'Email verified successfully.',
    'email_already_verified' => 'Email address is already verified.',
    'invalid_verification_link' => 'Invalid or expired verification link.',
    'verification_link_sent' => 'Verification link sent to your email address.',

    // Token messages
    'invalid_token' => 'Invalid or expired token.',
    'token_expired' => 'Token has expired.',
    'unauthorized' => 'Unauthorized access.',

    // Password reset messages
    'password_reset_sent' => 'Password reset link sent to your email.',
    'password_reset_successful' => 'Password reset successful.',
    'invalid_reset_token' => 'Invalid or expired reset token.',

    // Account status messages
    'account_disabled' => 'Your account has been disabled.',
    'account_locked' => 'Your account has been locked due to multiple failed login attempts.',
    'account_suspended' => 'Your account has been suspended.',
];
