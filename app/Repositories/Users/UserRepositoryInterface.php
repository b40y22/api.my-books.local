<?php

declare(strict_types=1);

namespace App\Repositories\Users;

use App\Http\Dto\Request\Auth\RegisterDto;
use App\Models\User;
use App\Repositories\AbstractRepositoryInterface;

interface UserRepositoryInterface extends AbstractRepositoryInterface
{
    /**
     * Create new user from registration data
     */
    public function store(RegisterDto $registerData): User;

    /**
     * Find user by email address
     */
    public function findByEmail(string $email): ?User;

    /**
     * Check if user exists by email
     */
    public function existsByEmail(string $email): bool;
}
