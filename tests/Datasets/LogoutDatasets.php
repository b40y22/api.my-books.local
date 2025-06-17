<?php

declare(strict_types=1);

namespace Tests\Datasets;

final class LogoutDatasets
{
    /**
     * @return array[]
     */
    public static function positiveTestCases(): array
    {
        return [
            'single token logout' => [
                'tokensToCreate' => 1,
                'expectedRemainingTokens' => 0,
                'endpoint' => 'logout',
            ],
            'multiple tokens logout current only' => [
                'tokensToCreate' => 3,
                'expectedRemainingTokens' => 2,
                'endpoint' => 'logout',
            ],
            'single token logout all' => [
                'tokensToCreate' => 1,
                'expectedRemainingTokens' => 0,
                'endpoint' => 'logout-all',
            ],
            'multiple tokens logout all' => [
                'tokensToCreate' => 5,
                'expectedRemainingTokens' => 0,
                'endpoint' => 'logout-all',
            ],
        ];
    }

    /**
     * @return array[]
     */
    public static function negativeTestCases(): array
    {
        return [
            'logout all with no active sessions' => [
                'endpoint' => 'logout-all',
                'expectedStatus' => 401,
                'expectedError' => 'Authentication required. Please provide a valid token.',
                'authenticated' => true,
                'deleteTokensFirst' => true,
            ],
        ];
    }

    /**
     * @return array[]
     */
    public static function structureTestCases(): array
    {
        return [
            'successful logout structure' => [
                'endpoint' => 'logout',
                'expectedStatus' => 200,
                'expectedKeys' => ['message'],
                'expectedMessage' => 'Logout successful.',
            ],
            'successful logout all structure' => [
                'endpoint' => 'logout-all',
                'expectedStatus' => 200,
                'expectedKeys' => ['message'],
                'expectedMessage' => 'Logged out from all devices successfully.',
            ],
        ];
    }

    /**
     * @return array[]
     */
    public static function tokenManagementTestCases(): array
    {
        return [
            'logout preserves other user tokens' => [
                'endpoint' => 'logout',
                'createOtherUserTokens' => true,
            ],
            'logout all preserves other user tokens' => [
                'endpoint' => 'logout-all',
                'createOtherUserTokens' => true,
            ],
        ];
    }

    /**
     * @return array[]
     */
    public static function consistencyTestCases(): array
    {
        return [
            'logout success scenario' => [
                'endpoint' => 'logout',
                'expectedStatus' => 200,
                'authenticated' => true,
            ],
            'logout all success scenario' => [
                'endpoint' => 'logout-all',
                'expectedStatus' => 200,
                'authenticated' => true,
            ],
            'logout unauthenticated scenario' => [
                'endpoint' => 'logout',
                'expectedStatus' => 401,
                'authenticated' => false,
            ],
            'logout all unauthenticated scenario' => [
                'endpoint' => 'logout-all',
                'expectedStatus' => 401,
                'authenticated' => false,
            ],
        ];
    }
}
