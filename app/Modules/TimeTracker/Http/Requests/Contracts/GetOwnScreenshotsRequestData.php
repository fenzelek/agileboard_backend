<?php

namespace App\Modules\TimeTracker\Http\Requests\Contracts;

interface GetOwnScreenshotsRequestData
{
    public function getDate(): string;

    public function getSelectedCompanyId(): int;

    public function getProjectId(): ?int;

}
