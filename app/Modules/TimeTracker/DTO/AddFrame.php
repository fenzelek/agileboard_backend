<?php

namespace App\Modules\TimeTracker\DTO;

use App\Modules\TimeTracker\DTO\Contracts\IAddFrame;

class AddFrame implements IAddFrame
{
    private array $input;

    public function __construct(array $input)
    {
        $this->input = $input;
    }

    public function getFrom(): int
    {
        return (int) $this->input['from'];
    }

    public function getTo(): int
    {
        return (int) $this->input['to'];
    }

    public function getCompanyId(): int
    {
        return (int) $this->input['companyId'];
    }

    public function getProjectId(): int
    {
        return (int) $this->input['projectId'];
    }

    public function getTaskId(): int
    {
        return (int) $this->input['taskId'];
    }

    public function getActivity(): int
    {
        return (int) $this->input['activity'];
    }

    public function getGpsLatitude(): ?float
    {
        return isset($this->input['gpsPosition']['latitude']) ? (float) $this->input['gpsPosition']['latitude'] : null;
    }

    public function getGpsLongitude(): ?float
    {
        return isset($this->input['gpsPosition']['longitude']) ? (float) $this->input['gpsPosition']['longitude'] : null;
    }

    public function getScreenshots(): array
    {
        return $this->input['screens'] ?? [];
    }
}
