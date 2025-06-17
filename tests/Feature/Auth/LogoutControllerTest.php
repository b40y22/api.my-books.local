<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\Datasets\LogoutDatasets;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create authenticated user
    $this->user = User::factory()->create(['email_verified_at' => now()]);
    Event::fake();
    Queue::fake();
});

function makeLogoutRequest(string $endpoint, ?string $token = null): Illuminate\Testing\TestResponse
{
    $headers = [];
    if ($token) {
        $headers['Authorization'] = "Bearer {$token}";
    }

    return test()->postJson("/api/auth/{$endpoint}", [], $headers);
}

describe('positive logout tests', function () {
    test('successful logout scenarios', function (int $tokensToCreate, int $expectedRemainingTokens, string $endpoint) {
        // Arrange - create multiple tokens
        $tokens = [];
        for ($i = 0; $i < $tokensToCreate; $i++) {
            $tokens[] = $this->user->createToken("test_token_{$i}")->plainTextToken;
        }

        // Use the last created token for authentication
        $authToken = end($tokens);

        // Act
        $response = makeLogoutRequest($endpoint, $authToken);

        // Assert
        $response->assertStatus(200);

        expect($response->json())
            ->toHaveKeys(['data', 'errors', 'request_id'])
            ->and($response->json('data'))
            ->toHaveKey('message')
            ->and($response->json('data.message'))->toBeString()
            ->and($response->json('errors'))->toBeEmpty();

        // Verify token management
        $this->user->refresh();
        expect($this->user->tokens()->count())->toBe($expectedRemainingTokens);

        // Verify current token is deleted for logout (but not logout-all)
        if ($endpoint === 'logout') {
            $tokenExists = PersonalAccessToken::findToken($authToken) !== null;
            expect($tokenExists)->toBeFalse();
        }
    })->with(LogoutDatasets::positiveTestCases());

    test('logout preserves other users tokens', function (string $endpoint, bool $createOtherUserTokens) {
        // Arrange
        $otherUser = User::factory()->create(['email_verified_at' => now()]);
        $otherUserToken = $otherUser->createToken('other_user_token');

        $currentUserToken = $this->user->createToken('current_user_token')->plainTextToken;

        $initialOtherUserTokenCount = $otherUser->tokens()->count();

        // Act
        makeLogoutRequest($endpoint, $currentUserToken);

        // Assert - other user tokens should remain untouched
        $otherUser->refresh();
        expect($otherUser->tokens()->count())->toBe($initialOtherUserTokenCount);
    })->with(LogoutDatasets::tokenManagementTestCases());
});

describe('negative logout tests', function () {
    test('logout fails with various invalid scenarios', function (string $endpoint, int $expectedStatus, string $expectedError, bool $authenticated, ?bool $deleteTokensFirst = null) {
        // Arrange
        $token = null;

        if ($authenticated) {
            $plainTextToken = $this->user->createToken('test_token')->plainTextToken;

            if ($deleteTokensFirst) {
                // Delete all tokens to simulate no active tokens scenario
                $this->user->tokens()->delete();
            } else {
                $token = $plainTextToken;
            }
        }

        // Act
        $response = makeLogoutRequest($endpoint, $token);

        // Assert
        $response->assertStatus($expectedStatus);

        expect($response->json())
            ->toHaveKeys(['data', 'errors'])
            ->and($response->json('data'))->toBeEmpty()
            ->and($response->json('errors'))->toContain($expectedError);
    })->with(LogoutDatasets::negativeTestCases());
});

describe('response structure tests', function () {
    test('response structure validation', function (string $endpoint, int $expectedStatus, array $expectedKeys, string $expectedMessage) {
        // Arrange
        $token = $this->user->createToken('test_token')->plainTextToken;

        // Act
        $response = makeLogoutRequest($endpoint, $token);

        // Assert
        $response->assertStatus($expectedStatus);

        expect($response->json())
            ->toHaveKeys(['data', 'errors', 'request_id'])
            ->and($response->json('data'))
            ->toHaveKeys($expectedKeys)
            ->and($response->json('data.message'))->toBe($expectedMessage)
            ->and($response->json('errors'))->toBeEmpty()
            ->and($response->json('request_id'))->toBeUuid();
    })->with(LogoutDatasets::structureTestCases());

    test('error response structure validation', function () {
        // Act - logout without authentication
        $response = makeLogoutRequest('logout');

        // Assert
        $response->assertStatus(401);

        expect($response->json())
            ->toBeValidationErrorResponse()
            ->and($response->json('request_id'))->toBeUuid();

        foreach ($response->json('errors') as $error) {
            expect($error)->toBeString();
        }
    });

    test('consistent response format across scenarios', function (string $endpoint, int $expectedStatus, bool $authenticated) {
        // Arrange
        $token = null;
        if ($authenticated) {
            $token = $this->user->createToken('test_token')->plainTextToken;
        }

        // Act
        $response = makeLogoutRequest($endpoint, $token);

        // Assert
        expect($response->json())->toBeApiResponse()
            ->and($response->getStatusCode())->toBe($expectedStatus)
            ->and($response->json('request_id'))->toBeUuid();
    })->with(LogoutDatasets::consistencyTestCases());
});
