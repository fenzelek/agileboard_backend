<?php

declare(strict_types=1);

namespace App\Modules\Notification\Contracts\Request;

interface INotificationRequest
{
    public function getReadFilter(): ?bool;

    public function getPage(): int;

    public function getPerPage(): ?int;
}
