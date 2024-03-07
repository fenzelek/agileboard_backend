<?php

namespace App\Modules\TimeTracker\Http\Requests\Contracts;

interface GetScreenshotsQueryData extends GetOwnScreenshotsRequestData
{
    public function getUserId(): int;
}
