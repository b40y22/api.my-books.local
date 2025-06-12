<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Exceptions\AuthenticationException;
use App\Exceptions\ValidationException;
use App\Http\Dto\Request\Auth\LoginDto;
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
     * @throws AuthenticationException
     * @throws ValidationException
     */
    public function authenticate(LoginDto $loginDto): array
    {
        // Find user by email
        $user = $this->userRepository->findByEmail($loginDto->email);

        if (!$user) {
            throw new AuthenticationException(__('auth.failed'));
        }

        // Check if email is verified
        if (!$user->hasVerifiedEmail()) {
            throw new ValidationException(__('auth.email_not_verified'));
        }

        // Verify password
        if (!Hash::check($loginDto->password, $user->password)) {
            throw new AuthenticationException(__('auth.failed'));
        }

        // Revoke existing tokens if not using remember me
        if (!$loginDto->remember) {
            $user->tokens()->delete();
        }

        // Create new access token
        $tokenName = $this->generateTokenName();
        $token = $user->createToken($tokenName);

        // Set remember me if requested
        if ($loginDto->remember) {
            Auth::login($user, true);
        }

        return [
            'user' => $user->toArray(),
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_in' => config('sanctum.expiration') ? config('sanctum.expiration') * 60 : null,
        ];
    }

    /**
     * Logout current user by revoking current access token
     */
    public function logout(): void
    {
        $user = Auth::user();

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        Auth::logout();
    }

    /**
     * Logout user from all devices by revoking all tokens
     */
    public function logoutFromAllDevices(): void
    {
        $user = Auth::user();

        $user?->tokens()->delete();

        Auth::logout();
    }

    /**
     * Generate unique token name for tracking
     */
    private function generateTokenName(): string
    {
        $deviceInfo = $this->getDeviceFingerprint();
        $timestamp = now()->format('Ymd_His');
        $random = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 6);

        return "auth_{$deviceInfo}_{$timestamp}_{$random}";
    }

    /**
     * Create device fingerprint without exposing sensitive data
     */
    private function getDeviceFingerprint(): string
    {
        $userAgent = request()->userAgent() ?? 'unknown';
        $ip = request()->ip() ?? 'unknown';

        // Create short hash instead of exposing real IP
        $fingerprint = hash('crc32', $userAgent . $ip);

        // Detect device type for better identification
        $deviceType = $this->detectDeviceType($userAgent);

        return "{$deviceType}_{$fingerprint}";
    }

    /**
     * Detect device type from user agent
     */
    private function detectDeviceType(string $userAgent): string
    {
        $userAgent = strtolower($userAgent);

        if (str_contains($userAgent, 'mobile') || str_contains($userAgent, 'android')) {
            return 'mobile';
        }

        if (str_contains($userAgent, 'tablet') || str_contains($userAgent, 'ipad')) {
            return 'tablet';
        }

        if (str_contains($userAgent, 'postman') || str_contains($userAgent, 'insomnia')) {
            return 'api';
        }

        return 'desktop';
    }
}
