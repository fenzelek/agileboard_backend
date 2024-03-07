<?php

namespace App\Modules\CalendarAvailability\Contracts;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

interface CalendarAvailability
{
    /**
     * Find objects with calendar availability for selected period of time.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     *
     * @return Collection
     */
    public function find(Carbon $startDate, Carbon $endDate, array $sorts=[], ?string $department=null);

    /**
     * Find objects with calendar availability for selected period of time.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     *
     * @return Collection
     */
    public function findByIds(Carbon $startDate, Carbon $endDate, array $users_ids);
}
