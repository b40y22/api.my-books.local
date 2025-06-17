<?php

declare(strict_types=1);

namespace Tests\Datasets;

final class PasswordResetDatasets
{
    /**
     * @return array[]
     */
    public static function forgotPasswordPositiveTestCases(): array
    {
        return [
            'valid email sends reset link' => [
                'email' => 'test@example.com',
                'expectedStatus' => 200,
                'expectEmailSent' => true,
                'expectJobQueued' => true,
            ],
        ];
    }

    /**
     * @return array[]
     */
    public static function forgotPasswordNegativeTestCases(): array
    {
        return [
            'non-existent email' => [
                'email' => 'nonexistent@example.com',
                'expectedStatus' => 422,
                'expectedError' => 'We could not find a user with that email address.',
            ],
            'unverified email' => [
                'email' => 'unverified@example.com',
                'expectedStatus' => 422,
                'expectedError' => 'Please verify your email address before logging in.',
                'setupUnverifiedUser' => true,
            ],
            'empty email' => [
                'email' => '',
                'expectedStatus' => 422,
                'expectedError' => 'Email address is required.',
            ],
            'invalid email format' => [
                'email' => 'not-an-email',
                'expectedStatus' => 422,
                'expectedError' => 'Please provide a valid email address.',
            ],
            'missing email field' => [
                'email' => null,
                'expectedStatus' => 422,
                'expectedError' => 'Email address is required.',
            ],
        ];
    }

    /**
     * @return array[]
     */
    public static function resetPasswordPositiveTestCases(): array
    {
        return [
            'valid token resets password' => [
                'newPassword' => 'newpassword123',
                'expectedStatus' => 200,
                'expectTokensDeleted' => true,
            ],
            'strong password resets successfully' => [
                'newPassword' => 'VeryStr0ng!Password',
                'expectedStatus' => 200,
                'expectTokensDeleted' => true,
            ],
        ];
    }

    /**
     * @return array[]
     */
    public static function resetPasswordNegativeTestCases(): array
    {
        return [
            'invalid token' => [
                'expectedStatus' => 422,
                'expectedError' => 'This password reset token is invalid.',
                'token' => 'invalid-token',
                'email' => 'test@example.com',
                'password' => 'newpassword123',
                'passwordConfirmation' => null,
                'useValidToken' => false,
            ],
            'non-existent email' => [
                'expectedStatus' => 422,
                'expectedError' => 'We could not find a user with that email address.',
                'token' => null,
                'email' => 'nonexistent@example.com',
                'password' => 'newpassword123',
                'passwordConfirmation' => null,
                'useValidToken' => true,
            ],
            'password too short' => [
                'expectedStatus' => 422,
                'expectedError' => 'Password must be at least 8 characters.',
                'token' => null,
                'email' => 'test@example.com',
                'password' => '123',
                'passwordConfirmation' => null,
                'useValidToken' => true,
            ],
            'password confirmation mismatch' => [
                'expectedStatus' => 422,
                'expectedError' => 'Password confirmation does not match.',
                'token' => null,
                'email' => 'test@example.com',
                'password' => 'newpassword123',
                'passwordConfirmation' => 'differentpassword',
                'useValidToken' => true,
            ],
            'missing token' => [
                'expectedStatus' => 422,
                'expectedError' => 'Reset token is required.',
                'token' => '',
                'email' => 'test@example.com',
                'password' => 'newpassword123',
                'passwordConfirmation' => null,
                'useValidToken' => false,
            ],
            'missing email' => [
                'expectedStatus' => 422,
                'expectedError' => 'Email address is required.',
                'token' => null,
                'email' => '',
                'password' => 'newpassword123',
                'passwordConfirmation' => null,
                'useValidToken' => true,
            ],
            'missing password' => [
                'expectedStatus' => 422,
                'expectedError' => 'Password is required.',
                'token' => null,
                'email' => 'test@example.com',
                'password' => '',
                'passwordConfirmation' => null,
                'useValidToken' => true,
            ],
        ];
    }

    /**
     * @return array[]
     */
    public static function structureTestCases(): array
    {
        return [
            'forgot password success structure' => [
                'endpoint' => 'forgot-password',
                'data' => ['email' => 'test@example.com'],
                'expectedStatus' => 200,
                'expectedKeys' => ['message'],
                'expectedMessage' => 'We have emailed your password reset link.',
            ],
            'reset password success structure' => [
                'endpoint' => 'reset-password',
                'data' => null,
                'expectedStatus' => 200,
                'expectedKeys' => ['message'],
                'expectedMessage' => 'Your password has been reset.',
                'useValidToken' => true,
            ],
        ];
    }

    /**
     * @return array[]
     */
    public static function integrationTestCases(): array
    {
        return [
            'complete password reset flow' => [
                'originalPassword' => 'originalpassword123',
                'newPassword' => 'newpassword123',
                'expectedForgotStatus' => 200,
                'expectedResetStatus' => 200,
            ],
        ];
    }

    /**
     * @return array[]
     */
    public static function consistencyTestCases(): array
    {
        return [
            'forgot password success' => [
                'endpoint' => 'forgot-password',
                'data' => ['email' => 'test@example.com'],
                'expectedStatus' => 200,
                'useValidToken' => null,
            ],
            'forgot password validation error' => [
                'endpoint' => 'forgot-password',
                'data' => ['email' => ''],
                'expectedStatus' => 422,
                'useValidToken' => null,
            ],
            'reset password success' => [
                'endpoint' => 'reset-password',
                'data' => [],
                'expectedStatus' => 200,
                'useValidToken' => true,
            ],
            'reset password validation error' => [
                'endpoint' => 'reset-password',
                'data' => ['token' => '', 'email' => '', 'password' => ''],
                'expectedStatus' => 422,
                'useValidToken' => null,
            ],
        ];
    }
}
