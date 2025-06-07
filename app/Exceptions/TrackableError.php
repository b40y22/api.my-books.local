<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class TrackableError
{
    protected string $errorId;

    public function __construct(
        private readonly string $errorMessage,
    ) {
        $this->errorId = Str::uuid()->toString();
        $this->log();
    }

    public function getErrorId(): string
    {
        return $this->errorId;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    public function getErrorForResponse(): array
    {
        return [
            'error-message' => [$this->getErrorMessage()],
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
