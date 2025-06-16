<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable external HTTP requests during tests
        if (class_exists('\Illuminate\Http\Client\Factory')) {
            \Illuminate\Support\Facades\Http::preventStrayRequests();
        }
    }

    /**
     * Create a user for testing
     */
    protected function createUser(array $attributes = []): \App\Models\User
    {
        return \App\Models\User::factory()->create($attributes);
    }

    /**
     * Create verified user for testing
     */
    protected function createVerifiedUser(array $attributes = []): \App\Models\User
    {
        return \App\Models\User::factory()->create(array_merge([
            'email_verified_at' => now(),
        ], $attributes));
    }

    /**
     * Generate unique email for testing
     */
    protected function generateUniqueEmail(string $prefix = 'test'): string
    {
        return $prefix.'.'.time().'.'.rand(1000, 9999).'@example.com';
    }
}
