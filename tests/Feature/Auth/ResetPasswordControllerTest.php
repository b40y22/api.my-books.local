<?php

declare(strict_types=1);

use App\Jobs\SendPasswordResetEmailJob;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Queue;
use Tests\Datasets\PasswordResetDatasets;
use \Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create verified user for structure tests
    $this->user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('originalpassword123'),
        'email_verified_at' => now(),
    ]);
    // Fake events, queues, and emails for testing
    Event::fake();
    Queue::fake();
    Mail::fake();
});

function makeForgotPasswordRequest(array $data): Illuminate\Testing\TestResponse
{
    return test()->postJson('/api/auth/forgot-password', $data);
}

function makeResetPasswordRequest(array $data): Illuminate\Testing\TestResponse
{
    return test()->postJson('/api/auth/reset-password', $data);
}

function makePasswordResetRequestWithToken(string $endpoint, array $data, ?bool $useValidToken = null, ?User $user = null): Illuminate\Testing\TestResponse
{
    $requestData = $data;

    if ($useValidToken && $user) {
        $token = Password::broker()->createToken($user);
        $requestData = [
            'token' => $token,
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ];
    }

    if ($endpoint === 'forgot-password') {
        return makeForgotPasswordRequest($requestData);
    } else {
        return makeResetPasswordRequest($requestData);
    }
}

describe('positive forgot password tests', function () {
    test('successful forgot password scenarios', function (string $email, int $expectedStatus, bool $expectEmailSent, bool $expectJobQueued) {
        // Act
        $response = makeForgotPasswordRequest(['email' => $email]);

        // Assert
        $response->assertStatus($expectedStatus);

        expect($response->json())
            ->toHaveKeys(['data', 'errors', 'request_id'])
            ->and($response->json('data'))
            ->toHaveKey('message')
            ->and($response->json('data.message'))->toBeString()
            ->and($response->json('errors'))->toBeEmpty();

        if ($expectJobQueued) {
            // Verify password reset email job was queued
            Queue::assertPushed(SendPasswordResetEmailJob::class, function ($job) {
                return $job->user->email === $this->user->email;
            });
        }

        if ($expectEmailSent) {
            // Verify password reset token was created in database
            $this->assertDatabaseHas('password_reset_tokens', [
                'email' => $this->user->email
            ]);
        }
    })->with(PasswordResetDatasets::forgotPasswordPositiveTestCases());
});

describe('negative forgot password tests', function () {
    test('forgot password fails with various invalid inputs', function (?string $email, int $expectedStatus, string $expectedError, ?bool $setupUnverifiedUser = null) {
        // Arrange
        if ($setupUnverifiedUser && $email) {
            User::factory()->create([
                'email' => $email,
                'password' => Hash::make('password123'),
                'email_verified_at' => null,
            ]);
        }

        $requestData = [];
        if ($email !== null) {
            $requestData['email'] = $email;
        }

        // Act
        $response = makeForgotPasswordRequest($requestData);

        // Assert
        $response->assertStatus($expectedStatus);

        expect($response->json())
            ->toHaveKeys(['data', 'errors'])
            ->and($response->json('data'))->toBeEmpty()
            ->and($response->json('errors'))->toContain($expectedError);

        // Verify no password reset job was queued for failed requests
        Queue::assertNotPushed(SendPasswordResetEmailJob::class);
    })->with(PasswordResetDatasets::forgotPasswordNegativeTestCases());
});

describe('positive reset password tests', function () {
    test('successful reset password scenarios', function (string $newPassword, int $expectedStatus, bool $expectTokensDeleted) {
        // Arrange - create valid password reset token
        $token = Password::broker()->createToken($this->user);

        $resetData = [
            'token' => $token,
            'email' => $this->user->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ];

        // Store initial token count for verification
        $initialTokenCount = $this->user->tokens()->count();

        // Act
        $response = makeResetPasswordRequest($resetData);

        // Assert
        $response->assertStatus($expectedStatus);

        expect($response->json())
            ->toHaveKeys(['data', 'errors', 'request_id'])
            ->and($response->json('data'))
            ->toHaveKey('message')
            ->and($response->json('data.message'))->toBeString()
            ->and($response->json('errors'))->toBeEmpty();

        // Verify password was actually changed
        $this->user->refresh();
        expect(Hash::check($newPassword, $this->user->password))->toBeTrue()
            ->and(Hash::check('originalpassword123', $this->user->password))->toBeFalse();

        if ($expectTokensDeleted) {
            // Verify all user tokens were deleted after password reset
            expect($this->user->tokens()->count())->toBe(0);
        }

        // Verify password reset token was removed from database
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $this->user->email,
            'token' => $token,
        ]);
    })->with(PasswordResetDatasets::resetPasswordPositiveTestCases());
});

describe('negative reset password tests', function () {
    test('reset password fails with various invalid inputs', function (int $expectedStatus, string $expectedError, ?string $token = null, ?string $email = null, ?string $password = null, ?string $passwordConfirmation = null, bool $useValidToken = false) {
        // Arrange
        $resetData = [];

        if ($useValidToken) {
            $resetData['token'] = Password::broker()->createToken($this->user);
        } elseif ($token !== null) {
            $resetData['token'] = $token;
        }

        if ($email !== null) {
            $resetData['email'] = $email;
        }

        if ($password !== null) {
            $resetData['password'] = $password;
        }

        if ($passwordConfirmation !== null) {
            $resetData['password_confirmation'] = $passwordConfirmation;
        } elseif ($password !== null && $password !== '') {
            // Default to matching confirmation unless explicitly different
            $resetData['password_confirmation'] = $password;
        }

        // Store original password hash for verification
        $originalPasswordHash = $this->user->password;

        // Act
        $response = makeResetPasswordRequest($resetData);

        // Assert
        $response->assertStatus($expectedStatus);

        expect($response->json())
            ->toHaveKeys(['data', 'errors'])
            ->and($response->json('data'))->toBeEmpty()
            ->and($response->json('errors'))->toContain($expectedError);

        // Verify password was NOT changed
        $this->user->refresh();
        expect($this->user->password)->toBe($originalPasswordHash);
    })->with(PasswordResetDatasets::resetPasswordNegativeTestCases());
});

describe('password reset integration tests', function () {
    test('complete password reset flow', function (string $originalPassword, string $newPassword, int $expectedForgotStatus, int $expectedResetStatus) {
        // Arrange - create user with original password
        $user = User::factory()->create([
            'email' => 'integration@example.com',
            'password' => Hash::make($originalPassword),
            'email_verified_at' => now(),
        ]);

        // Step 1: Request password reset
        $forgotResponse = makeForgotPasswordRequest(['email' => $user->email]);

        expect($forgotResponse->status())->toBe($expectedForgotStatus);

        // Verify job was queued
        Queue::assertPushed(SendPasswordResetEmailJob::class);

        // Step 2: Generate a new token (simulate the process)
        // Instead of using the hashed token from DB, create a fresh one
        $plainToken = Str::random(60);

        // Store the token in the password_reset_tokens table manually
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'email' => $user->email,
                'token' => Hash::make($plainToken),
                'created_at' => now()
            ]
        );

        // Step 3: Reset password with plain token
        $resetResponse = makeResetPasswordRequest([
            'token' => $plainToken,
            'email' => $user->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        // Debug if still failing
        if ($resetResponse->status() !== $expectedResetStatus) {
            dump("Expected reset status: {$expectedResetStatus}, Got: {$resetResponse->status()}");
            dump("Reset response:", $resetResponse->json());
        }

        expect($resetResponse->status())->toBe($expectedResetStatus);

        // Step 4: Verify password was changed
        $user->refresh();
        expect(Hash::check($newPassword, $user->password))->toBeTrue()
            ->and(Hash::check($originalPassword, $user->password))->toBeFalse();

        // Step 5: Verify token was removed
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);
    })->with(PasswordResetDatasets::integrationTestCases());
});

describe('response structure tests', function () {
    test('response structure validation', function (string $endpoint, ?array $data = null, ?int $expectedStatus = null, ?array $expectedKeys = null, ?string $expectedMessage = null, ?bool $useValidToken = null) {
        // Arrange
        $response = makePasswordResetRequestWithToken($endpoint, $data ?? [], $useValidToken, $this->user);

        // Assert
        if ($expectedStatus) {
            $response->assertStatus($expectedStatus);
        }

        expect($response->json())
            ->toHaveKeys(['data', 'errors', 'request_id'])
            ->and($response->json('request_id'))->toBeUuid();

        if ($expectedKeys) {
            expect($response->json('data'))
                ->toHaveKeys($expectedKeys)
                ->and($response->json('errors'))->toBeEmpty();

            if ($expectedMessage) {
                expect($response->json('data.message'))->toBe($expectedMessage);
            }
        }
    })->with(PasswordResetDatasets::structureTestCases());

    test('error response structure validation', function () {
        // Act - forgot password with invalid email
        $response = makeForgotPasswordRequest(['email' => '']);

        // Assert
        $response->assertStatus(422);

        expect($response->json())
            ->toBeValidationErrorResponse()
            ->and($response->json('request_id'))->toBeUuid();

        foreach ($response->json('errors') as $error) {
            expect($error)->toBeString();
        }
    });

    test('consistent response format across scenarios', function (string $endpoint, array $data, int $expectedStatus, ?bool $useValidToken = null) {
        // Arrange
        $response = makePasswordResetRequestWithToken($endpoint, $data ?? [], $useValidToken, $this->user);

        // Assert
        expect($response->json())->toBeApiResponse()
            ->and($response->getStatusCode())->toBe($expectedStatus)
            ->and($response->json('request_id'))->toBeUuid();
    })->with(PasswordResetDatasets::consistencyTestCases());
});
