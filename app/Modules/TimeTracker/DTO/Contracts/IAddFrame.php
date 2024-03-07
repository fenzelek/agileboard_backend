<?php

namespace App\Modules\TimeTracker\DTO\Contracts;

interface IAddFrame
{
    public function getFrom(): int;

    public function getTo(): int;

    public function getCompanyId(): int;

    public function getProjectId(): int;

    public function getTaskId(): int;

    public function getActivity(): int;

    public function getGpsLatitude(): ?float;

    public function getGpsLongitude(): ?float;

    public function getScreenshots(): array;
}
