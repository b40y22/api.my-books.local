<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Exceptions\AuthenticationException;
use App\Exceptions\ValidationException;
use App\Http\Dto\Request\Auth\LoginDto;
use App\Http\Dto\Response\Auth\LoginDto as ResponseLoginDto;
use App\Models\User;
use App\Repositories\Users\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

final readonly class LoginService implements LoginServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    /**
     * Authenticate user with email and password, return token and user data
     */
    public function authenticate(LoginDto $loginDto): array
    {
        $user = $this->userRepository->findByEmail($loginDto->email);

        if (! $user) {
            throw new AuthenticationException(__('auth.failed'));
        }

        if (! $user->hasVerifiedEmail()) {
            throw new ValidationException(__('auth.email_not_verified'));
        }

        if (! Hash::check($loginDto->password, $user->password)) {
            throw new AuthenticationException(__('auth.failed'));
        }

        if (! $loginDto->remember) {
            $user->tokens()->delete();
        }

        $tokenName = $this->generateTokenName();
        $token = $user->createToken($tokenName);

        if ($loginDto->remember) {
            Auth::login($user, true);
        }

        return new ResponseLoginDto($user)->toArray();
    }

    /**
     * Logout current user by revoking current access token
     */
    public function logout(): void
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException(__('auth.logout_failed_no_user'));
        }

        $currentToken = $user->currentAccessToken();

        if (! $currentToken) {
            throw new AuthenticationException(__('auth.logout_failed_no_token'));
        }

        $currentToken->delete();
    }

    /**
     * Logout user from all devices by revoking all tokens
     */
    public function logoutFromAllDevices(): void
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException(__('auth.logout_failed_no_user'));
        }

        $tokenCount = $user->tokens()->count();

        if ($tokenCount === 0) {
            throw new AuthenticationException(__('auth.logout_no_active_sessions'));
        }

        $user->tokens()->delete();
    }

    private function generateTokenName(): string
    {
        $deviceInfo = $this->getDeviceFingerprint();
        $timestamp = now()->format('Ymd_His');
        $random = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 6);

        return "auth_{$deviceInfo}_{$timestamp}_{$random}";
    }

    private function getDeviceFingerprint(): string
    {
        $userAgent = request()->userAgent() ?? 'unknown';
        $ip = request()->ip() ?? 'unknown';

        $fingerprint = hash('crc32', $userAgent.$ip);

        $deviceType = $this->detectDeviceType($userAgent);

        return "{$deviceType}_{$fingerprint}";
    }

    private function detectDeviceType(string $userAgent): string
    {
        $userAgent = strtolower($userAgent);

        return match (true) {
            str_contains($userAgent, 'mobile') || str_contains($userAgent, 'android') => 'mobile',
            str_contains($userAgent, 'tablet') || str_contains($userAgent, 'ipad') => 'tablet',
            str_contains($userAgent, 'postman') || str_contains($userAgent, 'insomnia') => 'api',
            default => 'desktop'
        };
    }
}
