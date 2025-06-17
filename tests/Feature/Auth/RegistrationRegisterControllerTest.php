<?php

declare(strict_types=1);

use App\Events\Auth\UserRegistered;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Datasets\RegistrationDatasets;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Event::fake();
    Queue::fake();
});

function makeRegistrationRequest(array $data): Illuminate\Testing\TestResponse
{
    return test()->postJson(route('auth.register'), $data);
}

describe('positive registration tests', function () {
    test('registration with valid data', function (array $data, int $expectedStatus, array $expectedResponse) {
        // Act
        $response = makeRegistrationRequest($data);

        // Assert
        $response->assertStatus($expectedStatus);

        expect($response->json())
            ->toHaveKeys(['data', 'errors'])
            ->and($response->json('data'))
            ->toHaveKeys(['name', 'email', 'token'])
            ->and($response->json('data.email'))->toBe($data['email'])
            ->and($response->json('data.token'))->not->toBeEmpty()
            ->and($response->json('data.token'))->toBeString()
            ->and($response->json('errors'))->toBeEmpty()
            ->and(User::where('email', $data['email'])->exists())->toBeTrue();

        if (! empty($additionalAttributes)) {
            $user = User::where('email', $data['email'])->first();
            foreach ($additionalAttributes as $key => $value) {
                expect($user->$key)->toBe($value);
            }
        }

        $user = User::where('email', $data['email'])->first();
        // Verify security
        expect($user)->not->toBeNull()
            ->and(Illuminate\Support\Facades\Hash::check($data['password'], $user->password))->toBeTrue()
            ->and($user->password)->not->toBe($data['password'])
            ->and($response->json('data'))->not->toHaveKey('password');

    })->with(RegistrationDatasets::positiveTestCases());

    test('registration fires UserRegistered event', function (array $data, string $expectedName) {
        $uniqueEmail = 'event.test.'.time().'@example.com';
        $testData = array_merge($data, ['email' => $uniqueEmail]);

        // Act
        $response = makeRegistrationRequest($testData);

        // Assert
        $response->assertStatus(201);

        // Verify UserRegistered
        Event::assertDispatched(UserRegistered::class, function ($event) use ($uniqueEmail, $expectedName) {
            return $event->user->email === $uniqueEmail
                && $event->user->name === $expectedName;
        });

    })->with(RegistrationDatasets::eventTestCases());

    test('registration creates correct full name', function (array $input, string $expected) {
        // Arrange
        $timestamp = time();
        $randomId = rand(1000, 9999);

        $data = array_merge($input, [
            'email' => "test.name.{$timestamp}.{$randomId}@example.com",
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Act
        $response = makeRegistrationRequest($data);

        // Assert
        $response->assertStatus(201);
        $user = User::where('email', $data['email'])->first();

        expect(trim($user->name))->toBe($expected);

    })->with(RegistrationDatasets::fullNameTestCases());
});

describe('negative registration tests', function () {
    test('registration with invalid data', function (array $data, int $expectedStatus, string $expectedError, bool $setupUser = false) {
        if ($setupUser && isset($data['email'])) {
            User::factory()->create(['email' => $data['email']]);
        }

        // Act
        $response = makeRegistrationRequest($data);

        // Assert
        $response->assertStatus(422);

        expect($response->json('errors'))
            ->toContain($expectedError)
            ->and($response->json('data'))->toBeEmpty()
            ->and($response->getStatusCode())->toBe($expectedStatus);

        // Verify no user created (except for duplicate email test)
        if (! $setupUser && isset($data['email']) && filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            expect(User::where('email', $data['email'])->exists())->toBeFalse();
        }

    })->with(RegistrationDatasets::negativeTestCases());

    test('registration does not fire event on validation failure', function () {
        // Arrange
        Event::fake();

        // Act
        makeRegistrationRequest([
            'firstname' => '', // Invalid
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Assert
        Event::assertNotDispatched(UserRegistered::class);
    });

    test('duplicate email registration maintains database integrity', function () {
        $email = 'duplicate.'.time().'@example.com';
        $existingUser = User::factory()->create(['email' => $email]);
        $userCountBefore = User::count();

        $duplicateData = array_merge([
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => 'test.user.'.time().'@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], ['email' => $email]);

        // Act
        $response = makeRegistrationRequest($duplicateData);

        // Assert
        $response->assertStatus(422);
        expect(User::count())->toBe($userCountBefore);

        // Verify original user unchanged
        $existingUser->refresh();
        expect($existingUser->name)->not->toContain('Test User');
    });
});

describe('response structure tests', function () {
    test('successful registration response has correct structure', function (array $data, array $expectedKeys, array $excludedKeys) {
        // Act
        $response = makeRegistrationRequest($data);

        // Assert
        $response->assertStatus(201);

        expect($response->json())
            ->toHaveKeys(['data', 'errors'])
            ->and($response->json('data'))->toHaveKeys($expectedKeys['data'])
            ->and($response->json('errors'))->toBeEmpty();

        // Verify excluded keys
        foreach ($excludedKeys as $excludedKey) {
            expect($response->json('data'))->not->toHaveKey($excludedKey);
        }

        // Verify data types and formats
        $responseData = $response->json('data');
        expect($responseData['name'])->toBeString()
            ->and($responseData['email'])->toBeValidEmail()
            ->and($responseData['token'])->toBeSanctumToken();

    })->with(RegistrationDatasets::structureTestCases());

    test('validation error response has correct structure', function () {
        // Act
        $response = makeRegistrationRequest([
            'firstname' => '',
            'email' => 'invalid-email',
            'password' => '123',
        ]);

        // Assert
        expect($response->json())->toBeValidationErrorResponse();

        foreach ($response->json('errors') as $error) {
            expect($error)->toBeString();
        }
    });

    test('response includes request id', function (array $data) {
        $testData = array_merge($data, [
            'email' => 'header.test.'.time().'@example.com',
        ]);

        // Act
        $response = makeRegistrationRequest($testData);

        // Assert
        expect($response->json('request_id'))->toBeUuid();

    })->with(RegistrationDatasets::requestIdTestCases());

    test('consistent response format across scenarios', function (array $data, int $expectedStatus) {
        if ($expectedStatus === 201) {
            $testData = array_merge($data, [
                'email' => 'success.'.time().'@example.com',
            ]);
        } else {
            $testData = $data;
        }

        // Act
        $response = makeRegistrationRequest($testData);

        // Assert
        expect($response->json())->toBeApiResponse()
            ->and($response->getStatusCode())->toBe($expectedStatus);

    })->with(RegistrationDatasets::consistencyTestCases());
});
