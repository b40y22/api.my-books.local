<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\Datasets\LoginDatasets;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Event::fake();
    Queue::fake();
});

function makeLoginRequest(array $data): Illuminate\Testing\TestResponse
{
    return test()->postJson(route('auth.login'), $data);
}

describe('positive login tests', function () {
    beforeEach(function () {
        // Create verified user for login tests
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);
    });

    test('successful login scenarios', function (array $data, int $expectedStatus) {
        // Act
        $response = makeLoginRequest($data);

        // Assert
        $response->assertStatus($expectedStatus);

        // Verify token is valid
        expect($response->json())
            ->toHaveKeys(['data', 'errors', 'request_id'])
            ->and($response->json('data'))
            ->toHaveKeys(['name', 'email', 'token'])
            ->and($response->json('data.email'))->toBe($this->user->email)
            ->and($response->json('data.name'))->toBe($this->user->name)
            ->and($response->json('data.token'))->not->toBeEmpty()
            ->and($response->json('data.token'))->toBeString()
            ->and($response->json('errors'))->toBeEmpty()
            ->and($response->json('data.token'))->toBeSanctumToken();

        // Verify user has active token
        $this->user->refresh();
        expect($this->user->tokens()->count())->toBeGreaterThan(0);
    })->with(LoginDatasets::positiveTestCases());

    test('login creates token with device fingerprint', function (array $data, string $expectedTokenPattern) {
        // Act
        $response = makeLoginRequest($data);

        // Assert
        $response->assertStatus(200);

        $this->user->refresh();
        $token = $this->user->tokens()->first();

        expect($token->name)
            ->toBeString()
            ->and($token->name)->toContain('auth_')
            ->and($token->name)->toMatch($expectedTokenPattern);
    })->with(LoginDatasets::deviceFingerprintTestCases());

    test('remember me functionality', function (array $data, bool $expectTokenDeletion) {
        // Arrange
        if ($expectTokenDeletion) {
            $this->user->createToken('existing_token');
        }

        // Act
        $response = makeLoginRequest($data);

        // Assert
        $response->assertStatus(200);

        // Verify token functionality
        $this->user->refresh();
        expect($this->user->tokens()->count())->toBeGreaterThan(0)
            ->and($response->json('data.token'))->toBeSanctumToken();
    })->with(LoginDatasets::rememberMeTestCases());
});

describe('negative login tests', function () {
    beforeEach(function () {
        // Create verified user for negative tests
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);
    });

    test('login fails with various invalid inputs', function (array $data, int $expectedStatus, string $expectedError, bool $setupUnverifiedUser = false) {
        // Arrange
        if ($setupUnverifiedUser && isset($data['email'])) {
            User::factory()->create([
                'email' => $data['email'],
                'password' => Hash::make('password123'),
                'email_verified_at' => null,
            ]);
        }

        // Act
        $response = makeLoginRequest($data);

        // Assert
        $response->assertStatus($expectedStatus);

        expect($response->json())
            ->toHaveKeys(['data', 'errors'])
            ->and($response->json('data'))->toBeEmpty()
            ->and($response->json('errors'))->toContain($expectedError);
    })->with(LoginDatasets::negativeTestCases());
});

describe('response structure tests', function () {
    beforeEach(function () {
        // Create verified user for structure tests
        $this->user = User::factory()->create([
            'email' => 'structure@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);
    });

    test('response structure validation', function (array $data, ?array $expectedKeys = null, ?array $excludedPatterns = null, ?int $expectedStatus = null, ?bool $expectEmptyData = null, ?bool $expectErrors = null) {
        // Act
        $response = makeLoginRequest($data);

        // Assert status if provided
        if ($expectedStatus) {
            $response->assertStatus($expectedStatus);
        }

        // Basic structure validation
        expect($response->json())
            ->toHaveKeys(['data', 'errors', 'request_id'])
            ->and($response->json('request_id'))->toBeUuid();

        // Success response validation
        if ($expectedKeys) {
            expect($response->json('data'))
                ->toHaveKeys($expectedKeys)
                ->and($response->json('errors'))->toBeEmpty();

            // Verify data types
            $data = $response->json('data');
            expect($data['name'])->toBeString()
                ->and($data['email'])->toBeValidEmail()
                ->and($data['token'])->toBeSanctumToken();

            // Check excluded patterns
            if ($excludedPatterns) {
                $fullResponse = json_encode($response->json());
                foreach ($excludedPatterns as $pattern) {
                    expect($fullResponse)->not->toContain($pattern);
                }
            }
        }

        // Error response validation
        if ($expectEmptyData) {
            expect($response->json('data'))->toBeEmpty();
        }

        if ($expectErrors) {
            expect($response->json('errors'))->not->toBeEmpty();
            foreach ($response->json('errors') as $error) {
                expect($error)->toBeString();
            }
        }
    })->with(LoginDatasets::structureTestCases());

    test('consistent response format across scenarios', function (array $data, int $expectedStatus) {
        // Act
        $response = makeLoginRequest($data);

        // Assert
        expect($response->json())->toBeApiResponse()
            ->and($response->getStatusCode())->toBe($expectedStatus)
            ->and($response->json('request_id'))->toBeUuid();
    })->with(LoginDatasets::consistencyTestCases());
});
