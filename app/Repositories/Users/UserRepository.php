<?php

declare(strict_types=1);

namespace App\Repositories\Users;

use App\Http\Dto\Request\Auth\RegisterDto;
use App\Jobs\SendVerificationEmailJob;
use App\Models\User;
use App\Repositories\AbstractRepository;
use App\Services\Translation\Email\EmailTranslationServiceInterface;

final class UserRepository extends AbstractRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly EmailTranslationServiceInterface $emailTranslationService
    ) {
        parent::__construct(new User());
    }

    public function store(RegisterDto $registerData): User
    {
        $user = $this->model->create($registerData->toArray());

        $translations = $this->emailTranslationService->getEmailTranslations('register', $registerData->locale);

        dispatch(new SendVerificationEmailJob($user, $translations))->onQueue('emails');

        return $user;
    }
}
