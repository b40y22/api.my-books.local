<?php

declare(strict_types=1);

namespace App\Repositories\Users;

use App\Exceptions\DuplicateException;
use App\Http\Dto\Request\Auth\RegisterDto;
use App\Jobs\SendVerificationEmailJob;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use App\Services\Translation\Email\EmailTranslationServiceInterface;
use Exception;

final class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        public User $model,
        private EmailTranslationServiceInterface $emailTranslationService
    ) {}

    /**
     * @throws Exception
     */
    public function store(RegisterDto $registerData): User
    {
        $user = $this->model->where('email', $registerData->email)->exists();

        if ($user) {
            throw new DuplicateException('email');
        }

        $user = $this->model->create($registerData->toArray());

        $translations = $this->emailTranslationService->getEmailTranslations('register', $registerData->locale);

        dispatch(new SendVerificationEmailJob($user, $translations))->onQueue('emails');

        return $user;
    }
}
