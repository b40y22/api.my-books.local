<?php

declare(strict_types=1);

use App\Events\Auth\UserRegistered;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Datasets\RegistrationDatasets;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Only fake queues globally, handle events per test
    fakeQueues();
});

describe('positive registration tests', function () {
    test('registration with valid data', function (array $data, int $expectedStatus, array $expectedResponse) {
        // Arrange
        fakeEvents();

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
            ->and($response->json('errors'))->toBeEmpty();

        // Verify user creation
        assertDatabaseHasUser($data['email']);
        assertPasswordIsHashed($data['email'], $data['password']);

        // Verify security
        expect($response->json('data'))->not->toHaveKey('password');

    })->with(RegistrationDatasets::positiveTestCases());

    test('registration fires UserRegistered event', function (array $data, string $expectedName) {
        // Arrange
        $uniqueEmail = 'event.test.'.time().'@example.com';
        $testData = array_merge($data, ['email' => $uniqueEmail]);

        $eventFired = false;
        $capturedUser = null;

        Event::listen(UserRegistered::class, function ($event) use (&$eventFired, &$capturedUser) {
            $eventFired = true;
            $capturedUser = $event->user;
        });

        // Act
        $response = makeRegistrationRequest($testData);

        // Assert
        $response->assertStatus(201);
        expect($eventFired)->toBeTrue('UserRegistered event should have been fired')
            ->and($capturedUser)->not->toBeNull('Event should contain user data')
            ->and($capturedUser->email)->toBe($uniqueEmail)
            ->and($capturedUser->name)->toBe($expectedName);

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
        // Arrange
        fakeEvents();

        if ($setupUser && isset($data['email'])) {
            User::factory()->create(['email' => $data['email']]);
        }

        // Act
        $response = makeRegistrationRequest($data);

        // Assert
        assertValidationError($response, $expectedError);
        expect($response->getStatusCode())->toBe($expectedStatus);

        // Verify no user created (except for duplicate email test)
        if (! $setupUser && isset($data['email']) && filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            assertDatabaseMissingUser($data['email']);
        }

    })->with(RegistrationDatasets::negativeTestCases());

    test('registration does not fire event on validation failure', function () {
        // Arrange
        Event::fake();

        $invalidData = [
            'firstname' => '', // Invalid
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // Act
        makeRegistrationRequest($invalidData);

        // Assert
        Event::assertNotDispatched(UserRegistered::class);
    });

    test('duplicate email registration maintains database integrity', function () {
        // Arrange
        fakeEvents();

        $email = 'duplicate.'.time().'@example.com';
        $existingUser = User::factory()->create(['email' => $email]);
        $userCountBefore = User::count();

        $duplicateData = generateValidRegistrationData(['email' => $email]);

        // Act
        $response = makeRegistrationRequest($duplicateData);

        // Assert
        $response->assertStatus(422);
        expect(User::count())->toBe($userCountBefore);

        // Verify original user unchanged
        $existingUser->refresh();
        expect($existingUser->name)->not->toContain('Test User'); // From generateValidRegistrationData
    });
});

describe('response structure tests', function () {
    test('successful registration response has correct structure', function (array $data, array $expectedKeys, array $excludedKeys) {
        // Arrange
        fakeEvents();

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
        // Arrange
        fakeEvents();

        $invalidData = [
            'firstname' => '',
            'email' => 'invalid-email',
            'password' => '123',
        ];

        // Act
        $response = makeRegistrationRequest($invalidData);

        // Assert
        expect($response->json())->toBeValidationErrorResponse();

        foreach ($response->json('errors') as $error) {
            expect($error)->toBeString();
        }
    });

    test('response includes request id', function (array $data) {
        // Arrange
        fakeEvents();

        $testData = array_merge($data, [
            'email' => 'header.test.'.time().'@example.com',
        ]);

        // Act
        $response = makeRegistrationRequest($testData);

        // Assert
        expect($response->json('request_id'))->toBeUuid();

    })->with(RegistrationDatasets::requestIdTestCases());

    test('consistent response format across scenarios', function (array $data, int $expectedStatus) {
        // Arrange
        fakeEvents();

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
