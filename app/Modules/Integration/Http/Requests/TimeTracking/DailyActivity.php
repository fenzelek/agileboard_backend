<?php

namespace App\Modules\Integration\Http\Requests\TimeTracking;

use App\Http\Requests\Request;
use App\Modules\Integration\Services\Contracts\DailyActivityEntryData;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class DailyActivity extends Request implements DailyActivityEntryData
{
    public function rules(): array
    {
        return [
            'started_at' => ['required', 'date_format:Y-m-d', 'before_or_equal:finished_at'],
            'finished_at' => ['required', 'date_format:Y-m-d', 'after_or_equal:started_at'],
            'selected_company_id' => ['required'],
            'time_zone_offset' => ['nullable', 'integer'],
        ];
    }

    public function getUserId(): int
    {
        return $this->user()->id;
    }

    public function getCompanyId(): int
    {
        return $this->input('selected_company_id');
    }

    public function getStartedAt(): CarbonInterface
    {
        return Carbon::parse($this->input('started_at'));
    }

    public function getFinishedAt(): CarbonInterface
    {
        return Carbon::parse($this->input('finished_at'));
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
