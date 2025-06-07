<?php

declare(strict_types=1);

namespace App\Rules;

use App\Exceptions\TrackableError;
use App\Exceptions\ValidationException;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Response;

final class UserExist implements ValidationRule
{
    /**
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     * @return void
     * @throws ValidationException
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = User::where('email', $value)->exists();

        if ($user) {
            $error = new TrackableError(__('validation.email.unique'));

            throw new ValidationException($error);
        }
    }
}
