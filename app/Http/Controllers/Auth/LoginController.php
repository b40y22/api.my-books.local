<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Exceptions\AuthenticationException;
use App\Exceptions\ValidationException;
use App\Http\Controllers\Controller;
use App\Http\Dto\Request\Auth\LoginDto;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\LoginServiceInterface;
use Illuminate\Http\JsonResponse;

final class LoginController extends Controller
{
    public function __construct(
        private readonly LoginServiceInterface $loginService
    ) {}

    /**
     * @throws ValidationException
     * @throws AuthenticationException
     */
    public function __invoke(LoginRequest $request): JsonResponse
    {
        $result = $this->loginService->authenticate(
            $request->validatedDTO(LoginDto::class)
        );

        return $this->success($result);
    }
}
