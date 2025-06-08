<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class Error
{
    protected string $errorId;

    public function __construct(
        private readonly string|array $errorMessage,
    ) {
        $this->errorId = Str::uuid()->toString();
        $this->log();
    }

    public function getErrorId(): string
    {
        return $this->errorId;
    }

    public function getErrorMessage(): string|array
    {
        return $this->errorMessage;
    }

    public function getErrorForResponse(): array
    {
        $errors = [];

        if ($this->getErrorMessage()) {
            $errors = is_array($this->errorMessage) ? $this->errorMessage : [$this->errorMessage];
        }

        return [
            'error-messages' => $errors,
            'error-id' => $this->getErrorId(),
        ];
    }

    public function log(): self
    {
        Log::error($this->errorMessage, [
            'error_id' => $this->errorId,
        ]);

        return $this;
    }
}
