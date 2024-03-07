<?php

declare(strict_types=1);

namespace App\Modules\CalendarAvailability\Models;

use Carbon\Carbon;

class AvailabilityExportDto
{
    private ?bool $is_user_available;

    private ?bool $is_overtime;

    private ?Carbon $day;

    private ?string $time_start;

    private ?string $time_stop;

    private ?string $description;

    private ?string $status;

    private ?string $department;

    private int $user_id;

    private string $user_first_name;

    private string $user_last_name;

    public function __construct(
        ?bool $is_user_available,
        ?bool $is_overtime,
        ?Carbon $day,
        ?string $time_start,
        ?string $time_stop,
        ?string $description,
        ?string $status,
        ?string $department,
        int $user_id,
        string $user_first_name,
        string $user_last_name
    ) {
        $this->is_user_available = $is_user_available;
        $this->is_overtime = $is_overtime;
        $this->day = $day;
        $this->time_start = $time_start;
        $this->time_stop = $time_stop;
        $this->description = $description;
        $this->status = $status;
        $this->department = $department;
        $this->user_id = $user_id;
        $this->user_first_name = $user_first_name;
        $this->user_last_name = $user_last_name;
    }

    public function getIsUserAvailable(): ?bool
    {
        return $this->is_user_available;
    }

    public function getIsOvertime(): ?bool
    {
        return $this->is_overtime;
    }

    public function getDay(): ?Carbon
    {
        return $this->day;
    }

    public function getTimeStart(): ?string
    {
        return $this->time_start;
    }

    public function getTimeStop(): ?string
    {
        return $this->time_stop;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getUserFirstName(): string
    {
        return $this->user_first_name;
    }

    public function getUserLastName(): string
    {
        return $this->user_last_name;
    }
}
