<?php

namespace App\Modules\Integration\Models;

use Carbon\Carbon;

class ActivityFromToDTO
{
    public Carbon $from;
    public Carbon $to;

    /**
     * @param Carbon $from
     * @param Carbon $to
     */
    public function __construct(Carbon $from, Carbon $to)
    {
        $this->from = $from;
        $this->to = $to;
    }
}
