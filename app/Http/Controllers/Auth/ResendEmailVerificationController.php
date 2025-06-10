<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\EmailVerificationServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ResendEmailVerificationController extends Controller
{
    public function __construct(
        private readonly EmailVerificationServiceInterface $emailVerificationService
    ) {}

    public function __invoke(): JsonResponse
    {
        return $this->success(
            $this->emailVerificationService->resendVerificationEmail(auth()->id())
        );
    }
}
