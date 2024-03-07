<?php

declare(strict_types=1);

namespace App\Models\Notification\Contracts;

interface ISendResult
{
    public function success(): bool;

    public function getFailReason(): ?string;
}
