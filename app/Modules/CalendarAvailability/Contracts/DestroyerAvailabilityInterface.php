<?php

namespace App\Modules\CalendarAvailability\Contracts;

interface DestroyerAvailabilityInterface
{
    public function destroy(int $company_id, string $day);
}
