<?php

namespace App\Modules\Notification\Models\Dto;

use App\Models\Notification\Contracts\ISendResult;

class SendResult implements ISendResult
{
    private bool $success;

    private ?string $fail_reason;

    public function __construct(bool $success, ?string $fail_reason=null)
    {
        $this->success = $success;
        $this->fail_reason = $fail_reason;
    }

    public function success(): bool
    {
        return $this->success;
    }

    public function getFailReason(): ?string
    {
        return $this->fail_reason;
    }
}
