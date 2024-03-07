<?php

declare(strict_types=1);

namespace App\Modules\Integration\Http\Resources;

use App\Modules\Integration\Models\ActivityReportDto;
use App\Modules\Integration\Models\ManualTicketActivityDto;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property ActivityReportDto $resource
 */
class ActivityReport extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->getUserId(),
            'email' => $this->resource->getUserEmail(),
            'first_name' => $this->resource->getUserFirstName(),
            'last_name' => $this->resource->getUserLastName(),
            'is_available' => $this->resource->getIsAvailable(),
            'availability_seconds' => $this->resource->getAvailabilitySeconds(),
            'work_progress' => $this->resource->getWorkProgress(),
            'tracking_activity_seconds' => $this->resource->getSumTrackingActivities(),
            'manual_activity_seconds' => $this->resource->getSumManualActivities(),
            'manual_activity_tickets' => $this->resource->getManualTickets()
                ->map(function (ManualTicketActivityDto $dto) {
                    return [
                        'id' => $dto->getTicketId(),
                        'title' => $dto->getTicketTitle(),
                        'name' => $dto->getTicketName(),
                        'manual_activity' => $dto->getManualActivity(),
                    ];
                }),
        ];
    }
}
