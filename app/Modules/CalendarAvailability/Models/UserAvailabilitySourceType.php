<?php

namespace App\Modules\CalendarAvailability\Models;

use App\Models\Db\UserAvailability;
use App\Modules\CalendarAvailability\Contracts\DaysOffInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class UserAvailabilitySourceType
{
    const INTERNAL = 'internal';
    const EXTERNAL = 'external';

    public static function all():array{
        return [
            self::INTERNAL,
            self::EXTERNAL,
        ];
    }
}
