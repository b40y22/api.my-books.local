<?php

declare(strict_types=1);

use App\Http\Middleware\Auth\LogoutAttemptMiddleware;
use App\Services\RequestLogger;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

beforeEach(function () {
    $this->mockRequestLogger = Mockery::mock('overload:' . RequestLogger::class);
    $this->middleware = new LogoutAttemptMiddleware();
});

afterEach(function () {
    Mockery::close();
});

describe('LogoutAttemptMiddleware Tests', function () {

    test('middleware basic functionality with no authentication', function () {
        // Arrange
        $request = Request::create('/api/auth/logout', 'POST');

        $next = fn($req) => new Response('test response');

        // Mock auth facade to return null
        $authMock = Mockery::mock('alias:Illuminate\Support\Facades\Auth');
        $authMock->shouldReceive('user')->andReturn(null);

        // Mock Log facade - allow any warnings
        $logMock = Mockery::mock('alias:Illuminate\Support\Facades\Log');
        $logMock->shouldReceive('warning')->zeroOrMoreTimes();

        // Allow any addEvent calls
        $this->mockRequestLogger
            ->shouldReceive('addEvent')
            ->zeroOrMoreTimes();

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert - just check that it doesn't throw and returns response
        expect($response->getContent())->toBe('test response');
    });

    test('middleware handles exceptions gracefully', function () {
        // Arrange
        $request = Request::create('/api/auth/logout', 'POST');

        $next = fn($req) => new Response('still works');

        // Mock auth facade to throw exception
        $authMock = Mockery::mock('alias:Illuminate\Support\Facades\Auth');
        $authMock->shouldReceive('user')->andThrow(new Exception('Auth failed'));

        // Mock Log facade
        $logMock = Mockery::mock('alias:Illuminate\Support\Facades\Log');
        $logMock->shouldReceive('warning')->zeroOrMoreTimes();

        // RequestLogger might not be called at all if auth fails early
        $this->mockRequestLogger
            ->shouldReceive('addEvent')
            ->zeroOrMoreTimes();

        // Act - should not throw
        $response = $this->middleware->handle($request, $next);

        // Assert
        expect($response->getContent())->toBe('still works');
    });

    test('middleware logs start and completion events when successful', function () {
        // Arrange
        $request = Request::create('/api/auth/logout', 'POST');
        $request->headers->set('User-Agent', 'Test Browser');
        $request->server->set('REMOTE_ADDR', '192.168.1.1');

        $next = fn($req) => new Response('logout successful', 200);

        // Mock auth facade to return null (unauthenticated)
        $authMock = Mockery::mock('alias:Illuminate\Support\Facades\Auth');
        $authMock->shouldReceive('user')->andReturn(null);

        // Mock Log facade
        $logMock = Mockery::mock('alias:Illuminate\Support\Facades\Log');
        $logMock->shouldReceive('warning')->zeroOrMoreTimes();

        // Expect start event
        $this->mockRequestLogger
            ->shouldReceive('addEvent')
            ->with('logout_attempt_started', Mockery::on(function ($data) {
                return $data['user_id'] === null &&
                    $data['email'] === null &&
                    $data['ip'] === '192.168.1.1' &&
                    $data['user_agent'] === 'Test Browser' &&
                    $data['logout_type'] === 'current_device';
            }))
            ->once();

        // Expect completion event
        $this->mockRequestLogger
            ->shouldReceive('addEvent')
            ->with('logout_attempt_completed', Mockery::on(function ($data) {
                return $data['user_id'] === null &&
                    $data['success'] === true &&
                    $data['status_code'] === 200 &&
                    $data['logout_type'] === 'current_device';
            }))
            ->once();

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        expect($response->getContent())->toBe('logout successful')
            ->and($response->getStatusCode())->toBe(200);
    });

    test('middleware distinguishes between logout and logout-all', function () {
        // Mock dependencies
        $authMock = Mockery::mock('alias:Illuminate\Support\Facades\Auth');
        $authMock->shouldReceive('user')->andReturn(null);

        $logMock = Mockery::mock('alias:Illuminate\Support\Facades\Log');
        $logMock->shouldReceive('warning')->zeroOrMoreTimes();

        $next = fn($req) => new Response('success');

        // Test regular logout
        $request1 = Request::create('/api/auth/logout', 'POST');

        $this->mockRequestLogger
            ->shouldReceive('addEvent')
            ->with('logout_attempt_started', Mockery::on(function ($data) {
                return $data['logout_type'] === 'current_device';
            }))
            ->once();

        $this->mockRequestLogger
            ->shouldReceive('addEvent')
            ->with('logout_attempt_completed', Mockery::type('array'))
            ->once();

        $this->middleware->handle($request1, $next);

        // Test logout-all
        $request2 = Request::create('/api/auth/logout-all', 'POST');

        $this->mockRequestLogger
            ->shouldReceive('addEvent')
            ->with('logout_attempt_started', Mockery::on(function ($data) {
                return $data['logout_type'] === 'all_devices';
            }))
            ->once();

        $this->mockRequestLogger
            ->shouldReceive('addEvent')
            ->with('logout_attempt_completed', Mockery::type('array'))
            ->once();

        $this->middleware->handle($request2, $next);
    });

    test('middleware works with authenticated user', function () {
        // Arrange
        $request = Request::create('/api/auth/logout', 'POST');

        // Mock user object
        $mockUser = Mockery::mock();
        $mockUser->id = 123;
        $mockUser->email = 'test@example.com';
        $mockUser->shouldReceive('currentAccessToken')->andReturn(null);

        // Mock auth facade
        $authMock = Mockery::mock('alias:Illuminate\Support\Facades\Auth');
        $authMock->shouldReceive('user')->andReturn($mockUser);

        // Mock Log facade
        $logMock = Mockery::mock('alias:Illuminate\Support\Facades\Log');
        $logMock->shouldReceive('warning')->zeroOrMoreTimes();

        $next = fn($req) => new Response('logout success', 200);

        // Expect logging with user data
        $this->mockRequestLogger
            ->shouldReceive('addEvent')
            ->with('logout_attempt_started', Mockery::on(function ($data) {
                return $data['user_id'] === 123 &&
                    $data['email'] === 'test@example.com';
            }))
            ->once();

        $this->mockRequestLogger
            ->shouldReceive('addEvent')
            ->with('logout_attempt_completed', Mockery::on(function ($data) {
                return $data['user_id'] === 123 &&
                    $data['email'] === 'test@example.com' &&
                    $data['success'] === true;
            }))
            ->once();

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        expect($response->getStatusCode())->toBe(200)
            ->and($response->getContent())->toBe('logout success');
    });

    test('middleware logs errors when RequestLogger fails', function () {
        // Arrange
        $request = Request::create('/api/auth/logout', 'POST');

        $next = fn($req) => new Response('continues despite error');

        // Mock auth
        $authMock = Mockery::mock('alias:Illuminate\Support\Facades\Auth');
        $authMock->shouldReceive('user')->andReturn(null);

        // Mock Log facade to expect specific warning calls
        $logMock = Mockery::mock('alias:Illuminate\Support\Facades\Log');
        $logMock->shouldReceive('warning')
            ->with('Failed to log logout attempt start', Mockery::type('array'))
            ->once();
        $logMock->shouldReceive('warning')
            ->with('Failed to log logout attempt completion', Mockery::type('array'))
            ->once();

        // Make RequestLogger throw exceptions
        $this->mockRequestLogger
            ->shouldReceive('addEvent')
            ->andThrow(new Exception('MongoDB connection failed'));

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert - should continue working despite logging failure
        expect($response->getContent())->toBe('continues despite error');
    });
});
