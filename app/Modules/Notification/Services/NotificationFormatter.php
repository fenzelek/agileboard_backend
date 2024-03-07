<?php

declare(strict_types=1);

namespace App\Modules\Notification\Services;

use App\Models\Other\NotificationType;
use App\Modules\Notification\Models\Dto\Notification;
use App\Modules\Notification\Notifications\InteractionNotification;
use App\Modules\Notification\Services\InteractionNotification\InteractionFormatter;
use App\Modules\Notification\Models\DatabaseNotification;
use Illuminate\Support\Collection;

class NotificationFormatter
{
    private InteractionFormatter $interaction_formatter;

    public function __construct(InteractionFormatter $interaction_formatter)
    {
        $this->interaction_formatter = $interaction_formatter;
    }

    /**
     * @param Collection|DatabaseNotification[] $notifications
     * @return Collection|Notification[]
     */
    public function format(Collection $notifications): Collection
    {
        return $notifications
            ->map(function (DatabaseNotification $notification) {
                return new Notification(
                    $notification->id,
                    $notification->created_at,
                    $notification->read_at,
                    $this->getType($notification->type),
                    $this->getData($notification),
                    $notification->company_id
                );
            });
    }

    private function getType(string $type): string
    {
        switch ($type) {
            case InteractionNotification::class:
                return NotificationType::INTERACTION;
            default:
                return $type;
        }
    }

    private function getData(DatabaseNotification $notification): array
    {
        switch ($notification->type) {
            case InteractionNotification::class:
                return $this->interaction_formatter->format($notification->data, $notification->company_id)->toArray();
            default:
                return $notification->data;
        }
    }
}
