<?php

declare(strict_types=1);

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

uses(Tests\TestCase::class)->in('Feature');

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

// ===================================
// AUTH HELPERS
// ===================================

if (! function_exists('makeRegistrationRequest')) {
    /**
     * Make registration API request
     */
    function makeRegistrationRequest(array $data): Illuminate\Testing\TestResponse
    {
        return test()->postJson('/api/auth/register', $data);
    }
}

if (! function_exists('makeLoginRequest')) {
    /**
     * Make login API request
     */
    function makeLoginRequest(array $data): Illuminate\Testing\TestResponse
    {
        return test()->postJson('/api/auth/login', $data);
    }
}

if (! function_exists('makeLogoutRequest')) {
    /**
     * Make logout API request
     */
    function makeLogoutRequest(?string $token = null): Illuminate\Testing\TestResponse
    {
        $headers = $token ? ['Authorization' => "Bearer {$token}"] : [];

        return test()->postJson('/api/auth/logout', [], $headers);
    }
}

if (! function_exists('makeEmailVerificationRequest')) {
    /**
     * Make email verification request
     */
    function makeEmailVerificationRequest(int $userId, string $hash, array $queryParams = []): Illuminate\Testing\TestResponse
    {
        $url = "/api/auth/verify-email/{$userId}/{$hash}";
        if (! empty($queryParams)) {
            $url .= '?'.http_build_query($queryParams);
        }

        return test()->getJson($url);
    }
}

// ===================================
// USER FACTORY HELPERS
// ===================================

if (! function_exists('createUser')) {
    /**
     * Create a user with specific attributes
     */
    function createUser(array $attributes = []): App\Models\User
    {
        return App\Models\User::factory()->create($attributes);
    }
}

if (! function_exists('createVerifiedUser')) {
    /**
     * Create a verified user
     */
    function createVerifiedUser(array $attributes = []): App\Models\User
    {
        return App\Models\User::factory()->create(array_merge([
            'email_verified_at' => now(),
        ], $attributes));
    }
}

if (! function_exists('createUnverifiedUser')) {
    /**
     * Create an unverified user
     */
    function createUnverifiedUser(array $attributes = []): App\Models\User
    {
        return App\Models\User::factory()->create(array_merge([
            'email_verified_at' => null,
        ], $attributes));
    }
}

if (! function_exists('createUserWithPassword')) {
    /**
     * Create user with specific password
     */
    function createUserWithPassword(string $email, string $password, array $attributes = []): App\Models\User
    {
        return App\Models\User::factory()->create(array_merge([
            'email' => $email,
            'password' => bcrypt($password),
        ], $attributes));
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
// ASSERTION HELPERS
// ===================================

if (! function_exists('assertApiResponse')) {
    /**
     * Assert response has correct API structure
     */
    function assertApiResponse(Illuminate\Testing\TestResponse $response, int $expectedStatus = 200): void
    {
        $response->assertStatus($expectedStatus);

        expect($response->json())
            ->toHaveKeys(['data', 'errors'])
            ->and($response->json('data'))->toBeArray()
            ->and($response->json('errors'))->toBeArray();
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

if (! function_exists('assertAuthenticationError')) {
    /**
     * Assert response contains authentication error
     */
    function assertAuthenticationError(Illuminate\Testing\TestResponse $response, ?string $expectedError = null): void
    {
        $response->assertStatus(401);

        expect($response->json())
            ->toHaveKeys(['data', 'errors'])
            ->and($response->json('data'))->toBeEmpty()
            ->and($response->json('errors'))->not->toBeEmpty();

        if ($expectedError) {
            expect($response->json('errors'))->toContain($expectedError);
        }
    }
}

if (! function_exists('assertRegistrationSuccess')) {
    /**
     * Assert successful registration response
     */
    function assertRegistrationSuccess(Illuminate\Testing\TestResponse $response, string $email): void
    {
        $response->assertStatus(201);

        expect($response->json())
            ->toHaveKeys(['data', 'errors'])
            ->and($response->json('data'))
            ->toHaveKeys(['name', 'email', 'token'])
            ->and($response->json('data.email'))->toBe($email)
            ->and($response->json('data.token'))->not->toBeEmpty()
            ->and($response->json('data.token'))->toBeString()
            ->and($response->json('errors'))->toBeEmpty();
    }
}

if (! function_exists('assertLoginSuccess')) {
    /**
     * Assert successful login response
     */
    function assertLoginSuccess(Illuminate\Testing\TestResponse $response, string $email): void
    {
        $response->assertStatus(200);

        expect($response->json())
            ->toHaveKeys(['data', 'errors'])
            ->and($response->json('data'))
            ->toHaveKeys(['name', 'email', 'token'])
            ->and($response->json('data.email'))->toBe($email)
            ->and($response->json('data.token'))->not->toBeEmpty()
            ->and($response->json('errors'))->toBeEmpty();
    }
}

if (! function_exists('assertEmailVerificationSuccess')) {
    /**
     * Assert successful email verification response
     */
    function assertEmailVerificationSuccess(Illuminate\Testing\TestResponse $response): void
    {
        $response->assertStatus(200);

        expect($response->json())
            ->toHaveKeys(['data', 'errors'])
            ->and($response->json('data.verified'))->toBeTrue()
            ->and($response->json('data.message'))->toBeString()
            ->and($response->json('errors'))->toBeEmpty();
    }
}

// ===================================
// DATABASE HELPERS
// ===================================

if (! function_exists('truncateTables')) {
    /**
     * Truncate specified tables
     */
    function truncateTables(array $tables): void
    {
        Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        foreach ($tables as $table) {
            Illuminate\Support\Facades\DB::table($table)->truncate();
        }

        Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
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

if (! function_exists('assertDatabaseMissingUser')) {
    /**
     * Assert user does not exist in database
     */
    function assertDatabaseMissingUser(string $email): void
    {
        expect(App\Models\User::where('email', $email)->exists())->toBeFalse();
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

if (! function_exists('assertUserIsVerified')) {
    /**
     * Assert user email is verified
     */
    function assertUserIsVerified(string $email): void
    {
        $user = App\Models\User::where('email', $email)->first();

        expect($user)->not->toBeNull()
            ->and($user->hasVerifiedEmail())->toBeTrue()
            ->and($user->email_verified_at)->not->toBeNull();
    }
}

if (! function_exists('assertUserIsNotVerified')) {
    /**
     * Assert user email is not verified
     */
    function assertUserIsNotVerified(string $email): void
    {
        $user = App\Models\User::where('email', $email)->first();

        expect($user)->not->toBeNull()
            ->and($user->hasVerifiedEmail())->toBeFalse()
            ->and($user->email_verified_at)->toBeNull();
    }
}

// ===================================
// TOKEN HELPERS
// ===================================

if (! function_exists('extractTokenFromResponse')) {
    /**
     * Extract token from API response
     */
    function extractTokenFromResponse(Illuminate\Testing\TestResponse $response): string
    {
        return $response->json('data.token');
    }
}

if (! function_exists('assertTokenFormat')) {
    /**
     * Assert token has correct Sanctum format
     */
    function assertTokenFormat(string $token): void
    {
        expect($token)->toMatch('/^\d+\|[\w\d]+$/');
    }
}

if (! function_exists('createAuthenticatedUser')) {
    /**
     * Create user and return with token
     */
    function createAuthenticatedUser(array $attributes = []): array
    {
        $user = createUser($attributes);
        $token = $user->createToken('test-token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }
}

// ===================================
// EVENT & QUEUE HELPERS
// ===================================

if (! function_exists('fakeEvents')) {
    /**
     * Fake events for testing
     */
    function fakeEvents(): void
    {
        Illuminate\Support\Facades\Event::fake();
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

if (! function_exists('assertEventDispatched')) {
    /**
     * Assert specific event was dispatched
     */
    function assertEventDispatched(string $eventClass, ?callable $callback = null): void
    {
        if ($callback) {
            Illuminate\Support\Facades\Event::assertDispatched($eventClass, $callback);
        } else {
            Illuminate\Support\Facades\Event::assertDispatched($eventClass);
        }
    }
}

if (! function_exists('assertEventNotDispatched')) {
    /**
     * Assert specific event was not dispatched
     */
    function assertEventNotDispatched(string $eventClass): void
    {
        Illuminate\Support\Facades\Event::assertNotDispatched($eventClass);
    }
}

// ===================================
// URL & ROUTING HELPERS
// ===================================

if (! function_exists('generateVerificationUrl')) {
    /**
     * Generate email verification URL
     */
    function generateVerificationUrl(App\Models\User $user): string
    {
        return Illuminate\Support\Facades\URL::temporarySignedRoute(
            'auth.verification.verify',
            Carbon\Carbon::now()->addMinutes(60),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );
    }
}

if (! function_exists('parseVerificationUrl')) {
    /**
     * Parse verification URL into components
     */
    function parseVerificationUrl(string $url): array
    {
        $urlParts = parse_url($url);
        parse_str($urlParts['query'] ?? '', $queryParams);

        return [
            'path' => $urlParts['path'],
            'query' => $queryParams,
        ];
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
