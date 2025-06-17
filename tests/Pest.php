<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(TestCase::class)->in('Feature');
uses(RefreshDatabase::class)->in('Feature');

beforeEach(function () {
    $this->seed();
});

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

if (! function_exists('fakeEvents')) {
    /**
     * Fake events for testing
     */
    function fakeEvents(): void
    {
        Illuminate\Support\Facades\Event::fake();
    }
}

if (! function_exists('makeRegistrationRequest')) {
    /**
     * Make registration API request
     */
    function makeRegistrationRequest(array $data): Illuminate\Testing\TestResponse
    {
        return test()->postJson('/api/auth/register', $data);
    }
}

if (! function_exists('fakeQueues')) {
    /**
     * Fake queues for testing
     */
    function fakeQueues(): void
    {
        Illuminate\Support\Facades\Queue::fake();
    }
}

if (! function_exists('assertDatabaseHasUser')) {
    /**
     * Assert user exists in database
     */
    function assertDatabaseHasUser(string $email, array $additionalAttributes = []): void
    {
        expect(App\Models\User::where('email', $email)->exists())->toBeTrue();

        if (! empty($additionalAttributes)) {
            $user = App\Models\User::where('email', $email)->first();
            foreach ($additionalAttributes as $key => $value) {
                expect($user->$key)->toBe($value);
            }
        }
    }
}

if (! function_exists('assertValidationError')) {
    /**
     * Assert response contains validation error
     */
    function assertValidationError(Illuminate\Testing\TestResponse $response, string $expectedError): void
    {
        $response->assertStatus(422);

        expect($response->json('errors'))
            ->toContain($expectedError)
            ->and($response->json('data'))->toBeEmpty();
    }
}

if (! function_exists('assertPasswordIsHashed')) {
    /**
     * Assert password is properly hashed
     */
    function assertPasswordIsHashed(string $email, string $plainPassword): void
    {
        $user = App\Models\User::where('email', $email)->first();

        expect($user)->not->toBeNull()
            ->and(Illuminate\Support\Facades\Hash::check($plainPassword, $user->password))->toBeTrue()
            ->and($user->password)->not->toBe($plainPassword);
    }
}

if (! function_exists('assertDatabaseMissingUser')) {
    /**
     * Assert user does not exist in database
     */
    function assertDatabaseMissingUser(string $email): void
    {
        expect(App\Models\User::where('email', $email)->exists())->toBeFalse();
    }
}

// ===================================
// DATA GENERATORS
// ===================================
if (! function_exists('generateValidRegistrationData')) {
    /**
     * Generate valid registration data
     */
    function generateValidRegistrationData(array $overrides = []): array
    {
        return array_merge([
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => 'test.user.'.time().'@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);
    }
}

if (! function_exists('generateValidLoginData')) {
    /**
     * Generate valid login data
     */
    function generateValidLoginData(array $overrides = []): array
    {
        return array_merge([
            'email' => 'test@example.com',
            'password' => 'password123',
        ], $overrides);
    }
}

if (! function_exists('generateUniqueEmail')) {
    /**
     * Generate unique email for testing
     */
    function generateUniqueEmail(string $prefix = 'test'): string
    {
        return $prefix.'.'.time().'.'.rand(1000, 9999).'@example.com';
    }
}

// ===================================
// CUSTOM EXPECTATIONS
// ===================================
expect()->extend('toBeValidEmail', function () {
    return $this->toMatch('/^[^\s@]+@[^\s@]+\.[^\s@]+$/');
});

expect()->extend('toBeUuid', function () {
    return $this->toMatch('/^[a-f0-9\-]{36}$/');
});

expect()->extend('toBeSanctumToken', function () {
    return $this->toMatch('/^\d+\|[\w\d]+$/');
});

expect()->extend('toBeApiResponse', function () {
    return $this->toHaveKeys(['data', 'errors']);
});

expect()->extend('toBeSuccessfulApiResponse', function () {
    return $this->toHaveKeys(['data', 'errors'])
        ->and($this->value['errors'])->toBeEmpty();
});

expect()->extend('toBeValidationErrorResponse', function () {
    return $this->toHaveKeys(['data', 'errors'])
        ->and($this->value['data'])->toBeEmpty()
        ->and($this->value['errors'])->not->toBeEmpty();
});

expect()->extend('toBeRegistrationSuccess', function () {
    return $this->toHaveKeys(['data', 'errors'])
        ->and($this->value['data'])->toHaveKeys(['name', 'email', 'token'])
        ->and($this->value['errors'])->toBeEmpty();
});
