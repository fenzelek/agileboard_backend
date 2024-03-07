<?php

declare(strict_types=1);

namespace App\Modules\Notification\Models\Dto;

class ReadResult
{
    private bool $success;

    private ?string $fail_reason;

    private array $errors;

    public function __construct(bool $success, ?string $fail_reason=null, array $errors=[])
    {
        $this->success = $success;
        $this->fail_reason = $fail_reason;
        $this->errors = $errors;
    }

    public function success(): bool
    {
        return $this->success;
    }

    public function getFailReason(): ?string
    {
        return $this->fail_reason;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
