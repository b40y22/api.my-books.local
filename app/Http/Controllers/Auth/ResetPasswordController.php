<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Dto\Request\Auth\ResetPasswordDto;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\Auth\PasswordResetServiceInterface;
use Illuminate\Http\JsonResponse;

final class ResetPasswordController extends Controller
{
    public function __construct(
        private readonly PasswordResetServiceInterface $passwordResetService
    ) {}

    public function __invoke(ResetPasswordRequest $request): JsonResponse
    {
        $resetPasswordDto = $request->validatedDTO(ResetPasswordDto::class);

        return $this->success(
            $this->passwordResetService->resetPassword($resetPasswordDto)
        );
    }
}
