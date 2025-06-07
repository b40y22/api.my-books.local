<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Services\Auth\EmailVerificationServiceInterface;
use Illuminate\Http\Request;

final readonly class EmailVerificationController
{
    public function __construct(
        private EmailVerificationServiceInterface $emailVerificationService
    ) {}

    public function __invoke(Request $request, int $userId, string $hash): void
    {
        $this->emailVerificationService->verifyEmail($request, $userId, $hash);
    }
}
