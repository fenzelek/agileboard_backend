<?php

namespace App\Modules\TimeTracker\Services\Contracts;

use App\Modules\TimeTracker\Http\Requests\Contracts\IAddScreens;
use App\Modules\TimeTracker\Models\Contracts\IScreenDBSaver;

interface IStorageScreenshot
{
    public function addScreenshot(IAddScreens $screen_files_provider, IScreenDBSaver $screen_service);
}
