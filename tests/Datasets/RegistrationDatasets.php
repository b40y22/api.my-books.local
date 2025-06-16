<?php

declare(strict_types=1);

namespace Tests\Datasets;

final class RegistrationDatasets
{
    /**
     * @return array[]
     */
    public static function negativeTestCases(): array
    {
        return [
            // Validation errors (422)
            'empty firstname' => [
                'data' => [
                    'firstname' => '',
                    'lastname' => 'Doe',
                    'email' => 'test@example.com',
                    'password' => 'password123',
                    'password_confirmation' => 'password123',
                ],
                'expectedStatus' => 422,
                'expectedError' => 'The firstname field is required.',
            ],
            'missing firstname' => [
                'data' => [
                    'lastname' => 'Doe',
                    'email' => 'test@example.com',
                    'password' => 'password123',
                    'password_confirmation' => 'password123',
                ],
                'expectedStatus' => 422,
                'expectedError' => 'The firstname field is required.',
            ],
            'firstname not string' => [
                'data' => [
                    'firstname' => 123,
                    'lastname' => 'Doe',
                    'email' => 'test@example.com',
                    'password' => 'password123',
                    'password_confirmation' => 'password123',
                ],
                'expectedStatus' => 422,
                'expectedError' => 'The firstname must be a string.',
            ],
            'lastname not string' => [
                'data' => [
                    'firstname' => 'John',
                    'lastname' => 123,
                    'email' => 'test@example.com',
                    'password' => 'password123',
                    'password_confirmation' => 'password123',
                ],
                'expectedStatus' => 422,
                'expectedError' => 'The lastname must be a string.',
            ],
            'empty email' => [
                'data' => [
                    'firstname' => 'John',
                    'lastname' => 'Doe',
                    'email' => '',
                    'password' => 'password123',
                    'password_confirmation' => 'password123',
                ],
                'expectedStatus' => 422,
                'expectedError' => 'The email field is required.',
            ],
            'missing email' => [
                'data' => [
                    'firstname' => 'John',
                    'lastname' => 'Doe',
                    'password' => 'password123',
                    'password_confirmation' => 'password123',
                ],
                'expectedStatus' => 422,
                'expectedError' => 'The email field is required.',
            ],
            'invalid email format' => [
                'data' => [
                    'firstname' => 'John',
                    'lastname' => 'Doe',
                    'email' => 'not-an-email',
                    'password' => 'password123',
                    'password_confirmation' => 'password123',
                ],
                'expectedStatus' => 422,
                'expectedError' => 'The email must be a valid email address.',
            ],
            'duplicate email' => [
                'data' => [
                    'firstname' => 'John',
                    'lastname' => 'Doe',
                    'email' => 'existing@example.com',
                    'password' => 'password123',
                    'password_confirmation' => 'password123',
                ],
                'expectedStatus' => 422,
                'expectedError' => 'The email has already been taken.',
                'setupUser' => true, // Flag to create existing user
            ],
            'empty password' => [
                'data' => [
                    'firstname' => 'John',
                    'lastname' => 'Doe',
                    'email' => 'test@example.com',
                    'password' => '',
                    'password_confirmation' => '',
                ],
                'expectedStatus' => 422,
                'expectedError' => 'The password field is required.',
            ],
            'missing password' => [
                'data' => [
                    'firstname' => 'John',
                    'lastname' => 'Doe',
                    'email' => 'test@example.com',
                    'password_confirmation' => 'password123',
                ],
                'expectedStatus' => 422,
                'expectedError' => 'The password field is required.',
            ],
            'password too short' => [
                'data' => [
                    'firstname' => 'John',
                    'lastname' => 'Doe',
                    'email' => 'test@example.com',
                    'password' => '123',
                    'password_confirmation' => '123',
                ],
                'expectedStatus' => 422,
                'expectedError' => 'The password must be at least 8 characters.',
            ],
            'password not string' => [
                'data' => [
                    'firstname' => 'John',
                    'lastname' => 'Doe',
                    'email' => 'test@example.com',
                    'password' => 12345678,
                    'password_confirmation' => 12345678,
                ],
                'expectedStatus' => 422,
                'expectedError' => 'The password must be a string.',
            ],
            'password confirmation mismatch' => [
                'data' => [
                    'firstname' => 'John',
                    'lastname' => 'Doe',
                    'email' => 'test@example.com',
                    'password' => 'password123',
                    'password_confirmation' => 'different123',
                ],
                'expectedStatus' => 422,
                'expectedError' => 'The password confirmation does not match.',
            ],
            'missing password confirmation' => [
                'data' => [
                    'firstname' => 'John',
                    'lastname' => 'Doe',
                    'email' => 'test@example.com',
                    'password' => 'password123',
                ],
                'expectedStatus' => 422,
                'expectedError' => 'The password confirmation does not match.',
            ],
        ];
    }

    /**
     * @return array[]
     */
    public static function invalidDataTestCases(): array
    {
        return [
            'empty firstname' => [
                'data' => ['firstname' => '', 'email' => 'test@example.com', 'password' => 'password123', 'password_confirmation' => 'password123'],
                'expectedError' => 'The firstname field is required.',
            ],
            'invalid email format' => [
                'data' => ['firstname' => 'John', 'email' => 'not-an-email', 'password' => 'password123', 'password_confirmation' => 'password123'],
                'expectedError' => 'The email must be a valid email address.',
            ],
            'password too short' => [
                'data' => ['firstname' => 'John', 'email' => 'test@example.com', 'password' => '123', 'password_confirmation' => '123'],
                'expectedError' => 'The password must be at least 8 characters.',
            ],
            'password confirmation mismatch' => [
                'data' => ['firstname' => 'John', 'email' => 'test@example.com', 'password' => 'password123', 'password_confirmation' => 'different123'],
                'expectedError' => 'The password confirmation does not match.',
            ],
        ];
    }

    /**
     * @return array[]
     */
    public static function fullNameTestCases(): array
    {
        return [
            'first and last name' => [
                'input' => ['firstname' => 'John', 'lastname' => 'Doe'],
                'expected' => 'John Doe',
            ],
            'first name only' => [
                'input' => ['firstname' => 'Jane', 'lastname' => null],
                'expected' => 'Jane',
            ],
            'first name with empty lastname' => [
                'input' => ['firstname' => 'Alice', 'lastname' => ''],
                'expected' => 'Alice',
            ],
            'complex name with suffix' => [
                'input' => ['firstname' => 'Bob', 'lastname' => 'Smith Jr.'],
                'expected' => 'Bob Smith Jr.',
            ],
        ];
    }

    /**
     * @return array[]
     */
    public static function eventTestCases(): array
    {
        return [
            'standard event test' => [
                'data' => [
                    'firstname' => 'Event',
                    'lastname' => 'Test',
                    'password' => 'password123',
                    'password_confirmation' => 'password123',
                ],
                'expectedName' => 'Event Test',
            ],
        ];
    }

    /**
     * @return array[]
     */
    public static function requestIdTestCases(): array
    {
        return [
            'request id test' => [
                'data' => [
                    'firstname' => 'Header',
                    'lastname' => 'Test',
                    'password' => 'password123',
                    'password_confirmation' => 'password123',
                ],
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
                    'firstname' => 'Success',
                    'password' => 'password123',
                    'password_confirmation' => 'password123',
                ],
                'expectedStatus' => 201,
            ],
            'validation error scenario' => [
                'data' => [
                    'firstname' => '',
                    'email' => 'invalid',
                    'password' => '123',
                ],
                'expectedStatus' => 422,
            ],
        ];
    }

    /**
     * @return array[]
     */
    public static function positiveTestCases(): array
    {
        $timestamp = time();

        return [
            'successful registration with lastname' => [
                'data' => [
                    'firstname' => 'John',
                    'lastname' => 'Doe',
                    'email' => "john.doe.{$timestamp}@example.com",
                    'password' => 'password123',
                    'password_confirmation' => 'password123',
                ],
                'expectedStatus' => 201,
                'expectedResponse' => [
                    'data' => [
                        'name' => 'John Doe',
                        'email' => "john.doe.{$timestamp}@example.com",
                    ],
                    'errors' => [],
                ],
            ],
            'successful registration without lastname' => [
                'data' => [
                    'firstname' => 'Jane',
                    'email' => "jane.{$timestamp}@example.com",
                    'password' => 'password123',
                    'password_confirmation' => 'password123',
                ],
                'expectedStatus' => 201,
                'expectedResponse' => [
                    'data' => [
                        'name' => 'Jane',
                        'email' => "jane.{$timestamp}@example.com",
                    ],
                    'errors' => [],
                ],
            ],
            'successful registration with empty lastname' => [
                'data' => [
                    'firstname' => 'Alice',
                    'lastname' => '',
                    'email' => "alice.{$timestamp}@example.com",
                    'password' => 'password123',
                    'password_confirmation' => 'password123',
                ],
                'expectedStatus' => 201,
                'expectedResponse' => [
                    'data' => [
                        'name' => 'Alice',
                        'email' => "alice.{$timestamp}@example.com",
                    ],
                    'errors' => [],
                ],
            ],
            'successful registration with long password' => [
                'data' => [
                    'firstname' => 'Bob',
                    'lastname' => 'Smith',
                    'email' => "bob.smith.{$timestamp}@example.com",
                    'password' => 'very-long-and-secure-password-123',
                    'password_confirmation' => 'very-long-and-secure-password-123',
                ],
                'expectedStatus' => 201,
                'expectedResponse' => [
                    'data' => [
                        'name' => 'Bob Smith',
                        'email' => "bob.smith.{$timestamp}@example.com",
                    ],
                    'errors' => [],
                ],
            ],
        ];
    }

    /**
     * @return array[]
     */
    public static function structureTestCases(): array
    {
        $timestamp = time();

        return [
            'standard structure test' => [
                'data' => [
                    'firstname' => 'Test',
                    'lastname' => 'User',
                    'email' => "structure.test.{$timestamp}@example.com",
                    'password' => 'password123',
                    'password_confirmation' => 'password123',
                ],
                'expectedKeys' => [
                    'data' => [
                        'name',
                        'email',
                        'token',
                    ],
                    'errors' => [],
                ],
                'excludedKeys' => [
                    'password',
                    'password_confirmation',
                    'remember_token',
                ],
            ],
        ];
    }
}
