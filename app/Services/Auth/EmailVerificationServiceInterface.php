<?php

namespace App\Services\Auth;

use Illuminate\Http\Request;

interface EmailVerificationServiceInterface
{
    public function verifyEmail(Request $request, int $userId, string $hash): array;

    public function resendVerificationEmail(int $userId): array;
}
