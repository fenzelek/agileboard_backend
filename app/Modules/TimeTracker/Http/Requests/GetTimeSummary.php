<?php

namespace App\Modules\TimeTracker\Http\Requests;

use App\Http\Requests\Request;
use App\Modules\Integration\Services\Contracts\IGetTimeSummary;

class GetTimeSummary extends Request implements IGetTimeSummary
{
    public function rules(): array
    {
        return [
            'time_zone_offset' => ['nullable', 'integer'],
        ];
    }

    public function getTimeZoneOffset(): int
    {
        $time_zone_offset = $this->input('time_zone_offset');
        if (! $time_zone_offset) {
            return 0;
        }
        return $time_zone_offset;
    }
}
