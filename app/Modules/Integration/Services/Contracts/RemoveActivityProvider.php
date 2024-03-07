<?php

namespace App\Modules\Integration\Services\Contracts;

interface RemoveActivityProvider
{
    public function getActivitiesIds(): array;

    public function getCompanyId(): int;
}
