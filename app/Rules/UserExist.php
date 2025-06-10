<?php

declare(strict_types=1);

namespace App\Rules;

use App\Exceptions\ValidationException;
use App\Models\User;
use App\Services\RequestLogger;
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
            // Log event only, exception will be logged in BaseFormRequest
            RequestLogger::addEvent('[rule] user_exists_validation_failed', [
                'email' => $value,
                'rule' => 'UserExist',
                'attribute' => $attribute,
            ]);

            $fail(__('validation.email.unique'));
        }
    }
}
