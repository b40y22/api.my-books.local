<?php

namespace App\Services\Translation\Email;

interface EmailTranslationServiceInterface
{
    public function getEmailTranslations(string $emailType, string $locale): array;
}
