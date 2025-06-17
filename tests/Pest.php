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
