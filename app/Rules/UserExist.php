<?php

declare(strict_types=1);

namespace App\Rules;

use App\Exceptions\Error;
use App\Exceptions\ValidationException;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class UserExist implements ValidationRule
{
    /**
     * @throws ValidationException
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = User::where('email', $value)->exists();

        if ($user) {
            throw new ValidationException(
                new Error(__('validation.email.unique'))
            );
        }
    }
}
