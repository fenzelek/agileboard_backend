<?php

declare(strict_types=1);

namespace App\Modules\Integration\Models;

use Illuminate\Support\Collection;

class ActivityReportDto
{
    private int $user_id;

    private string $user_email;

    private string $user_first_name;

    private string $user_last_name;

    private int $sum_manual_activities;

    private int $sum_tracking_activities;

    private bool $is_available;

    private ?int $availability_seconds;

    private ?float $work_progress;

    /** @var Collection|ManualTicketActivityDto[] */
    private Collection $manual_tickets;

    public function __construct(
        int $user_id,
        string $user_email,
        string $user_first_name,
        string $user_last_name,
        int $sum_manual_activities,
        int $sum_tracking_activities,
        bool $is_available,
        ?int $availability_seconds,
        ?float $work_progress,
        Collection $manual_tickets
    ) {
        $this->user_id = $user_id;
        $this->user_email = $user_email;
        $this->user_first_name = $user_first_name;
        $this->user_last_name = $user_last_name;
        $this->sum_manual_activities = $sum_manual_activities;
        $this->sum_tracking_activities = $sum_tracking_activities;
        $this->is_available = $is_available;
        $this->availability_seconds = $availability_seconds;
        $this->work_progress = $work_progress;
        $this->manual_tickets = $manual_tickets;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getUserEmail(): string
    {
        return $this->user_email;
    }

    public function getUserFirstName(): string
    {
        return $this->user_first_name;
    }

    public function getUserLastName(): string
    {
        return $this->user_last_name;
    }

    public function getSumManualActivities(): int
    {
        return $this->sum_manual_activities;
    }

    public function getSumTrackingActivities(): int
    {
        return $this->sum_tracking_activities;
    }

    public function getIsAvailable(): bool
    {
        return $this->is_available;
    }

    public function getAvailabilitySeconds(): ?int
    {
        return $this->availability_seconds;
    }

    public function getWorkProgress(): ?float
    {
        return $this->work_progress;
    }

    /** @return  Collection|ManualTicketActivityDto[] */
    public function getManualTickets(): Collection
    {
        return $this->manual_tickets;
    }
}
