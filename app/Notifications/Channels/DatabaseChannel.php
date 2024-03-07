<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Notifications\Notification;
use Exception;
use Illuminate\Notifications\Channels\DatabaseChannel as VendorDatabaseChannel;
use Illuminate\Notifications\Notification as VendorNotification;

class DatabaseChannel extends VendorDatabaseChannel
{
    /** @throws Exception */
    public function buildPayload($notifiable, VendorNotification $notification): array
    {
        $this->checkInstance($notification);

        /** @var Notification $notification */
        return [
            'id' => $notification->id,
            'type' => get_class($notification),
            'data' => $this->getData($notifiable, $notification),
            'read_at' => null,
            'company_id' => $notification->getCompanyId(),
        ];
    }

    /** @throws Exception */
    private function checkInstance(VendorNotification $notification)
    {
        if (! ($notification instanceof Notification)) {
            throw new \Exception('Notification must must be instanceof: ' . Notification::class);
        }
    }
}
