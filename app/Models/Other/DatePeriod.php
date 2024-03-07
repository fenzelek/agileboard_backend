<?php

namespace App\Models\Other;

use Carbon\Carbon;

class DatePeriod
{
    /**
     * @var string|null
     */
    public $start;

    /**
     * @var string|null
     */
    public $end;

    public function getStart()
    {
        return $this->start ?? Carbon::now()->toDateString();
    }

    public function getEnd()
    {
        return $this->end ?? Carbon::now()->toDateString();
    }
}
