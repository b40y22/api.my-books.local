<?php

declare(strict_types=1);

namespace App\Services\Translation\Email;

use App\Exceptions\NotFoundException;
use Illuminate\Support\Facades\Log;

class EmailTranslationService implements EmailTranslationServiceInterface
{
    /**
     * @param string $emailType
     * @param string $locale
     * @return array
     * @throws NotFoundException
     */
    public function getEmailTranslations(string $emailType, string $locale): array
    {
        if (! $this->localeExists($locale)) {
            throw new NotFoundException("Locale [{$locale}] not found.");
        }

        $currentLocale = app()->getLocale();
        app()->setLocale($locale);

        if (! $this->getAvailableEmailTypes($emailType)) {
            app()->setLocale($currentLocale);

            throw new NotFoundException("Email translations not found for type [{$emailType}].");
        }

        return __("email.{$emailType}");
    }

    private function getAvailableEmailTypes(string $emailType): bool
    {
        $emailTranslations = __('email');

        return array_key_exists($emailType, $emailTranslations);
    }

    public function localeExists(string $locale): bool
    {
        $langPath = base_path("lang/{$locale}");

        return is_dir($langPath);
    }
}
