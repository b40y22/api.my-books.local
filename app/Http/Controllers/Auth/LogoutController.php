<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\LoginServiceInterface;
use Illuminate\Http\JsonResponse;

final class LogoutController extends Controller
{
    public function __construct(
        private readonly LoginServiceInterface $loginService
    ) {}

    public function __invoke(): JsonResponse
    {
        $this->loginService->logout();

        return $this->success(['message' => __('auth.logout_successful')]);
    }

    public function logoutAll(): JsonResponse
    {
        $this->loginService->logoutFromAllDevices();

        return $this->success(['message' => __('auth.logout_all_successful')]);
    }
}
