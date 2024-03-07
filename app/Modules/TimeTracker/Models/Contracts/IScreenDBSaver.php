<?php

namespace App\Modules\TimeTracker\Models\Contracts;

interface IScreenDBSaver
{
    public function saveScreen(IScreenPaths $screen_paths): bool;
}
