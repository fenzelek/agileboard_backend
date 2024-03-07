<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Notification as VendorNotification;

abstract class Notification extends VendorNotification
{
    private ?int $company_id;

    public function __construct(?int $company_id)
    {
        $this->company_id = $company_id;
    }

    public function getCompanyId(): ?int
    {
        return $this->company_id;
    }
}
