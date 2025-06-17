<?php

declare(strict_types=1);

use App\Events\Auth\UserRegistered;
use App\Jobs\SendVerificationEmailJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Tests\Datasets\EmailVerificationDatasets;

uses(RefreshDatabase::class);

beforeEach(function () {
    Event::fake();
    Queue::fake();
    Mail::fake();
});

function makeVerificationRequest(int $userId, string $hash, ?string $expires = null, ?string $signature = null): Illuminate\Testing\TestResponse
{
    $url = route('auth.verification.verify', ['id' => $userId, 'hash' => $hash]);

    $queryParams = [];
    if ($expires) {
        $queryParams['expires'] = $expires;
    }
    if ($signature) {
        $queryParams['signature'] = $signature;
    }

    if (!empty($queryParams)) {
        $url .= '?' . http_build_query($queryParams);
    }

    return test()->getJson($url);
}

function makeResendVerificationRequest(?string $token = null): Illuminate\Testing\TestResponse
{
    $headers = [];
    if ($token) {
        $headers['Authorization'] = "Bearer {$token}";
    }

    return test()->postJson(route('auth.verification.resend'), [], $headers);
}

function generateVerificationUrl(User $user): array
{
    $verificationUrl = URL::temporarySignedRoute(
        'auth.verification.verify',
        now()->addMinutes(60),
        [
            'id' => $user->getKey(),
            'hash' => sha1($user->getEmailForVerification()),
        ]
    );

    $parsedUrl = parse_url($verificationUrl);
    parse_str($parsedUrl['query'] ?? '', $queryParams);

    return [
        'userId' => $user->getKey(),
        'hash' => sha1($user->getEmailForVerification()),
        'expires' => $queryParams['expires'] ?? null,
        'signature' => $queryParams['signature'] ?? null,
        'fullUrl' => $verificationUrl,
    ];
}

describe('positive email verification tests', function () {
    beforeEach(function () {
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => null,
        ]);
    });

    test('successful email verification scenarios', function (int $expectedStatus, bool $expectEmailVerified, bool $useValidSignature) {
        $verificationData = generateVerificationUrl($this->user);

        if (!$useValidSignature) {
            $verificationData['signature'] = 'invalid-signature';
        }

        $response = makeVerificationRequest(
            $verificationData['userId'],
            $verificationData['hash'],
            $verificationData['expires'],
            $verificationData['signature']
        );

        $response->assertStatus($expectedStatus);

        expect($response->json())
            ->toHaveKeys(['data', 'errors', 'request_id'])
            ->and($response->json('errors'))->toBeEmpty();

        if ($expectEmailVerified) {
            $this->user->refresh();
            expect($this->user->hasVerifiedEmail())->toBeTrue()
                ->and($response->json('data'))
                ->toHaveKey('verified')
                ->and($response->json('data.verified'))->toBeTrue();
        }
    })->with(EmailVerificationDatasets::positiveTestCases());
});

describe('negative email verification tests', function () {
    beforeEach(function () {
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => null,
        ]);
    });

    test('email verification fails with various invalid scenarios', function (int $expectedStatus, ?string $expectedError = null, ?string $expectedMessage = null, bool $useValidSignature = true, ?bool $tamperWithSignature = null, ?bool $useWrongUserId = null, ?bool $useWrongHash = null, ?bool $userAlreadyVerified = null) {
        if ($userAlreadyVerified) {
            $this->user->markEmailAsVerified();
        }

        $verificationData = generateVerificationUrl($this->user);

        if ($tamperWithSignature) {
            $verificationData['signature'] = 'tampered-signature';
        } elseif (!$useValidSignature) {
            $verificationData['signature'] = null;
        }

        if ($useWrongUserId) {
            $verificationData['userId'] = 99999;
        }

        if ($useWrongHash) {
            $verificationData['hash'] = 'wrong-hash';
        }

        $response = makeVerificationRequest(
            $verificationData['userId'],
            $verificationData['hash'],
            $verificationData['expires'],
            $verificationData['signature']
        );

        $response->assertStatus($expectedStatus);

        if ($expectedError) {
            if ($expectedStatus === 403) {
                expect($response->json())
                    ->toHaveKey('message')
                    ->and($response->json('message'))->toContain($expectedError);
            } elseif ($expectedStatus === 404) {
                $responseJson = $response->json();
                if (isset($responseJson['message'])) {
                    expect($responseJson['message'])->toContain($expectedError);
                } else {
                    expect($responseJson)
                        ->toHaveKeys(['data', 'errors'])
                        ->and($responseJson['data'])->toBeEmpty()
                        ->and($responseJson['errors'])->toContain($expectedError);
                }
            } else {
                expect($response->json())
                    ->toHaveKeys(['data', 'errors'])
                    ->and($response->json('data'))->toBeEmpty()
                    ->and($response->json('errors'))->toContain($expectedError);
            }
        }

        if ($expectedMessage) {
            expect($response->json('data.message'))->toBe($expectedMessage);
        }

        if ($expectedStatus >= 400 && !$userAlreadyVerified) {
            $this->user->refresh();
            expect($this->user->hasVerifiedEmail())->toBeFalse();
        }
    })->with(EmailVerificationDatasets::negativeTestCases());
});

describe('positive resend verification tests', function () {
    beforeEach(function () {
        $this->user = User::factory()->create([
            'email' => 'resend@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => null,
        ]);
    });

    test('successful resend verification scenarios', function (int $expectedStatus, bool $expectJobQueued, bool $userVerified) {
        if ($userVerified) {
            $this->user->markEmailAsVerified();
        }

        $token = $this->user->createToken('test_token')->plainTextToken;

        $response = makeResendVerificationRequest($token);

        $response->assertStatus($expectedStatus);

        expect($response->json())
            ->toHaveKeys(['data', 'errors', 'request_id'])
            ->and($response->json('data'))
            ->toHaveKey('message')
            ->and($response->json('errors'))->toBeEmpty();

        if ($expectJobQueued) {
            Event::assertDispatched(UserRegistered::class, function ($event) {
                return $event->user->email === $this->user->email;
            });
        } else {
            Event::assertNotDispatched(UserRegistered::class);
        }
    })->with(EmailVerificationDatasets::resendPositiveTestCases());
});

describe('negative resend verification tests', function () {
    beforeEach(function () {
        $this->user = User::factory()->create([
            'email' => 'resend@example.com',
            'password' => Hash::make('password123'),
        ]);
    });

    test('resend verification fails with various scenarios', function (int $expectedStatus, ?string $expectedError = null, ?string $expectedMessage = null, bool $userVerified = false, bool $authenticated = true) {
        if ($userVerified) {
            $this->user->markEmailAsVerified();
        }

        $token = null;
        if ($authenticated) {
            $token = $this->user->createToken('test_token')->plainTextToken;
        }

        $response = makeResendVerificationRequest($token);

        $response->assertStatus($expectedStatus);

        if ($expectedError) {
            if ($expectedStatus === 401) {
                $responseJson = $response->json();
                if (isset($responseJson['message'])) {
                    expect($responseJson['message'])->toContain($expectedError);
                } else {
                    expect($responseJson)
                        ->toHaveKeys(['data', 'errors'])
                        ->and($responseJson['data'])->toBeEmpty()
                        ->and($responseJson['errors'])->toContain($expectedError);
                }
            } else {
                expect($response->json())
                    ->toHaveKeys(['data', 'errors'])
                    ->and($response->json('data'))->toBeEmpty()
                    ->and($response->json('errors'))->toContain($expectedError);
            }
        }

        if ($expectedMessage) {
            expect($response->json('data.message'))->toBe($expectedMessage);
        }

        if ($expectedStatus >= 400) {
            Event::assertNotDispatched(UserRegistered::class);
        }
    })->with(EmailVerificationDatasets::resendNegativeTestCases());
});

describe('email verification integration tests', function () {
    test('complete email verification flow', function (string $userEmail, bool $expectRegistrationSuccess, bool $expectVerificationSuccess) {
        $registrationData = [
            'firstname' => 'Integration',
            'lastname' => 'Test',
            'email' => $userEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $registrationResponse = test()->postJson(route('auth.register'), $registrationData);

        if ($expectRegistrationSuccess) {
            $registrationResponse->assertStatus(201);
        }

        $user = User::where('email', $userEmail)->first();
        expect($user)->not->toBeNull()
            ->and($user->hasVerifiedEmail())->toBeFalse();

        $verificationData = generateVerificationUrl($user);

        $verificationResponse = makeVerificationRequest(
            $verificationData['userId'],
            $verificationData['hash'],
            $verificationData['expires'],
            $verificationData['signature']
        );

        if ($expectVerificationSuccess) {
            $verificationResponse->assertStatus(200);

            $user->refresh();
            expect($user->hasVerifiedEmail())->toBeTrue();
        }
    })->with(EmailVerificationDatasets::integrationTestCases());
});

describe('response structure tests', function () {
    beforeEach(function () {
        $this->user = User::factory()->create([
            'email' => 'structure@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => null,
        ]);
    });

    test('response structure validation', function (string $endpoint, int $expectedStatus, array $expectedKeys, string $expectedMessage) {
        if ($endpoint === 'verify-email') {
            $verificationData = generateVerificationUrl($this->user);
            $response = makeVerificationRequest(
                $verificationData['userId'],
                $verificationData['hash'],
                $verificationData['expires'],
                $verificationData['signature']
            );
        } else {
            $token = $this->user->createToken('test_token')->plainTextToken;
            $response = makeResendVerificationRequest($token);
        }

        $response->assertStatus($expectedStatus);

        expect($response->json())
            ->toHaveKeys(['data', 'errors', 'request_id'])
            ->and($response->json('data'))
            ->toHaveKeys($expectedKeys)
            ->and($response->json('data.message'))->toBe($expectedMessage)
            ->and($response->json('errors'))->toBeEmpty()
            ->and($response->json('request_id'))->toBeUuid();
    })->with(EmailVerificationDatasets::structureTestCases());

    test('error response structure validation', function () {
        $response = makeVerificationRequest(999, 'invalid-hash', (string)(time() + 3600), 'invalid-signature');

        $response->assertStatus(403);

        expect($response->json())
            ->toHaveKey('message')
            ->and($response->json('message'))->toBeString();
    });

    test('consistent response format across scenarios', function (string $endpoint, int $expectedStatus, ?bool $useValidData = null, ?bool $authenticated = null) {
        if ($endpoint === 'verify-email') {
            $verificationData = generateVerificationUrl($this->user);
            if (!$useValidData) {
                $verificationData['signature'] = 'invalid';
            }
            $response = makeVerificationRequest(
                $verificationData['userId'],
                $verificationData['hash'],
                $verificationData['expires'],
                $verificationData['signature']
            );
        } else {
            $token = null;
            if ($authenticated) {
                $token = $this->user->createToken('test_token')->plainTextToken;
            }
            $response = makeResendVerificationRequest($token);
        }

        $statusCode = $response->getStatusCode();
        expect($statusCode)->toBe($expectedStatus);

        if ($statusCode === 401 || $statusCode === 403) {
            $responseJson = $response->json();
            if (isset($responseJson['data'])) {
                expect($responseJson)->toBeApiResponse()
                    ->and($responseJson['request_id'])->toBeUuid();
            } else {
                expect($responseJson)->toHaveKey('message');
            }
        } else {
            expect($response->json())->toBeApiResponse()
                ->and($response->json('request_id'))->toBeUuid();
        }
    })->with(EmailVerificationDatasets::consistencyTestCases());
});
