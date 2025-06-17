<?php

declare(strict_types=1);

namespace Tests\Datasets;

final class EmailVerificationDatasets
{
    /**
     * @return array[]
     */
    public static function positiveTestCases(): array
    {
        return [
            'valid verification link verifies email' => [
                'expectedStatus' => 200,
                'expectEmailVerified' => true,
                'useValidSignature' => true,
            ],
        ];
    }

    /**
     * @return array[]
     */
    public static function negativeTestCases(): array
    {
        return [
            'invalid signature' => [
                'expectedStatus' => 403,
                'expectedError' => 'Invalid signature.',
                'useValidSignature' => false,
                'tamperWithSignature' => true,
            ],
            'wrong user id' => [
                'expectedStatus' => 403, // Laravel signature validation fails first
                'expectedError' => 'Invalid signature.',
                'useValidSignature' => true,
                'useWrongUserId' => true,
            ],
            'wrong hash' => [
                'expectedStatus' => 403, // Invalid signature because hash is part of URL
                'expectedError' => 'Invalid signature.',
                'useValidSignature' => true,
                'useWrongHash' => true,
            ],
            'already verified email' => [
                'expectedStatus' => 200,
                'expectedMessage' => 'Email address is already verified.',
                'useValidSignature' => true,
                'userAlreadyVerified' => true,
            ],
        ];
    }

    /**
     * @return array[]
     */
    public static function resendPositiveTestCases(): array
    {
        return [
            'resend verification to unverified user' => [
                'expectedStatus' => 200,
                'expectJobQueued' => true,
                'userVerified' => false,
            ],
        ];
    }

    /**
     * @return array[]
     */
    public static function resendNegativeTestCases(): array
    {
        return [
            'resend to already verified user' => [
                'expectedStatus' => 200,
                'expectedMessage' => 'Email address is already verified.',
                'userVerified' => true,
            ],
            'resend without authentication' => [
                'expectedStatus' => 401,
                'expectedError' => 'Authentication required. Please provide a valid token.',
                'authenticated' => false,
            ],
        ];
    }

    /**
     * @return array[]
     */
    public static function structureTestCases(): array
    {
        return [
            'successful verification structure' => [
                'endpoint' => 'verify-email',
                'expectedStatus' => 200,
                'expectedKeys' => ['message', 'verified'],
                'expectedMessage' => 'Email verified successfully.',
            ],
            'successful resend structure' => [
                'endpoint' => 'resend-verification',
                'expectedStatus' => 200,
                'expectedKeys' => ['message'],
                'expectedMessage' => 'Verification link sent to your email address.',
            ],
        ];
    }

    /**
     * @return array[]
     */
    public static function integrationTestCases(): array
    {
        return [
            'complete email verification flow' => [
                'userEmail' => 'integration@example.com',
                'expectRegistrationSuccess' => true,
                'expectVerificationSuccess' => true,
            ],
        ];
    }

    /**
     * @return array[]
     */
    public static function consistencyTestCases(): array
    {
        return [
            'verification success scenario' => [
                'endpoint' => 'verify-email',
                'expectedStatus' => 200,
                'useValidData' => true,
            ],
            'verification error scenario' => [
                'endpoint' => 'verify-email',
                'expectedStatus' => 403,
                'useValidData' => false,
            ],
            'resend success scenario' => [
                'endpoint' => 'resend-verification',
                'expectedStatus' => 200,
                'authenticated' => true,
            ],
            'resend error scenario' => [
                'endpoint' => 'resend-verification',
                'expectedStatus' => 401,
                'authenticated' => false,
            ],
        ];
    }
}
