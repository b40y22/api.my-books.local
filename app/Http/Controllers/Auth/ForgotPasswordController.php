<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Dto\Request\Auth\ForgotPasswordDto;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Services\Auth\PasswordResetServiceInterface;
use Illuminate\Http\JsonResponse;

final class ForgotPasswordController extends Controller
{
    public function __construct(
        private readonly PasswordResetServiceInterface $passwordResetService
    ) {}

    public function __invoke(ForgotPasswordRequest $request): JsonResponse
    {
        $forgotPasswordDto = $request->validatedDTO(ForgotPasswordDto::class);

        return $this->success(
            $this->passwordResetService->sendResetLink($forgotPasswordDto)
        );
    }
}
