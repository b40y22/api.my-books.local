<?php

declare(strict_types=1);

namespace Tests\Datasets;

final class LoginDatasets
{
    /**
     * @return array[]
     */
    public static function positiveTestCases(): array
    {
        return [
            'valid credentials' => [
                'data' => [
                    'email' => 'test@example.com',
                    'password' => 'password123',
                ],
                'expectedStatus' => 200,
            ],
            'valid credentials with remember me' => [
                'data' => [
                    'email' => 'test@example.com',
                    'password' => 'password123',
                    'remember' => true,
                ],
                'expectedStatus' => 200,
            ],
            'valid credentials without remember me' => [
                'data' => [
                    'email' => 'test@example.com',
                    'password' => 'password123',
                    'remember' => false,
                ],
                'expectedStatus' => 200,
            ],
        ];
    }

    /**
     * @return array[]
     */
    public static function negativeTestCases(): array
    {
        return [
            'non-existent email' => [
                'data' => [
                    'email' => 'nonexistent@example.com',
                    'password' => 'password123',
                ],
                'expectedStatus' => 401,
                'expectedError' => 'These credentials do not match our records.',
            ],
            'wrong password' => [
                'data' => [
                    'email' => 'test@example.com',
                    'password' => 'wrongpassword',
                ],
                'expectedStatus' => 401,
                'expectedError' => 'These credentials do not match our records.',
            ],
            'unverified email' => [
                'data' => [
                    'email' => 'unverified@example.com',
                    'password' => 'password123',
                ],
                'expectedStatus' => 422,
                'expectedError' => 'Please verify your email address before logging in.',
                'setupUnverifiedUser' => true,
            ],
            'empty email' => [
                'data' => [
                    'email' => '',
                    'password' => 'password123',
                ],
                'expectedStatus' => 422,
                'expectedError' => 'Email address is required.',
            ],
            'invalid email format' => [
                'data' => [
                    'email' => 'not-an-email',
                    'password' => 'password123',
                ],
                'expectedStatus' => 422,
                'expectedError' => 'Please provide a valid email address.',
            ],
            'empty password' => [
                'data' => [
                    'email' => 'test@example.com',
                    'password' => '',
                ],
                'expectedStatus' => 422,
                'expectedError' => 'Password is required.',
            ],
            'missing email field' => [
                'data' => [
                    'password' => 'password123',
                ],
                'expectedStatus' => 422,
                'expectedError' => 'Email address is required.',
            ],
            'missing password field' => [
                'data' => [
                    'email' => 'test@example.com',
                ],
                'expectedStatus' => 422,
                'expectedError' => 'Password is required.',
            ],
        ];
    }

    /**
     * @return array[]
     */
    public static function structureTestCases(): array
    {
        return [
            'successful login structure' => [
                'data' => [
                    'email' => 'structure@example.com',
                    'password' => 'password123',
                ],
                'expectedKeys' => ['name', 'email', 'token'],
                'excludedPatterns' => ['password'],
                'expectedStatus' => 200,
            ],
            'validation error structure' => [
                'data' => [
                    'email' => 'invalid-email',
                    'password' => '',
                ],
                'expectedStatus' => 422,
                'expectEmptyData' => true,
                'expectErrors' => true,
            ],
            'authentication error structure' => [
                'data' => [
                    'email' => 'nonexistent@example.com',
                    'password' => 'password123',
                ],
                'expectedStatus' => 401,
                'expectEmptyData' => true,
                'expectErrors' => true,
            ],
        ];
    }

    /**
     * @return array[]
     */
    public static function consistencyTestCases(): array
    {
        return [
            'success scenario' => [
                'data' => [
                    'email' => 'structure@example.com', // This matches the user in structure tests
                    'password' => 'password123',
                ],
                'expectedStatus' => 200,
            ],
            'validation error scenario' => [
                'data' => [
                    'email' => '',
                    'password' => '',
                ],
                'expectedStatus' => 422,
            ],
            'authentication error scenario' => [
                'data' => [
                    'email' => 'wrong@example.com',
                    'password' => 'wrongpassword',
                ],
                'expectedStatus' => 401,
            ],
        ];
    }

    /**
     * @return array[]
     */
    public static function deviceFingerprintTestCases(): array
    {
        return [
            'standard device fingerprint' => [
                'data' => [
                    'email' => 'test@example.com', // Use same email as in beforeEach
                    'password' => 'password123',
                ],
                'expectedTokenPattern' => '/^auth_\w+_\d{8}_\d{6}_\w{6}$/',
            ],
        ];
    }

    /**
     * @return array[]
     */
    public static function rememberMeTestCases(): array
    {
        return [
            'remember me true' => [
                'data' => [
                    'email' => 'test@example.com', // Use same email as in beforeEach
                    'password' => 'password123',
                    'remember' => true,
                ],
                'expectTokenDeletion' => false,
            ],
            'remember me false' => [
                'data' => [
                    'email' => 'test@example.com', // Use same email as in beforeEach
                    'password' => 'password123',
                    'remember' => false,
                ],
                'expectTokenDeletion' => true,
            ],
        ];
    }
}
