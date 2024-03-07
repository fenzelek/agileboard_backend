<?php

declare(strict_types=1);

namespace App\Modules\Integration\Http\Requests\TimeTracking;

use App\Http\Requests\Request;
use Carbon\Carbon;

class Report extends Request
{
    public function rules(): array
    {
        return [
            'day' => ['date_format:Y-m-d'],
        ];
    }

    public function getDate(): Carbon
    {
        $day = $this->query('day');
        return $day ? Carbon::parse($day) : Carbon::now();
    }
}
