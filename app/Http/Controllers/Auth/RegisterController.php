<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Dto\Request\Auth\RegisterDto;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Auth\RegisterServiceInterface;
use Illuminate\Http\JsonResponse;

final class RegisterController extends Controller
{
    public function __construct(
        private RegisterServiceInterface $userService
    ) {}

    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $userDto = RegisterDto::fromRequest($request);

        return $this->created(
            $this->userService->store($userDto)->toArray()
        );
    }
}
