<?php

namespace App\Modules\TimeTracker\Services;

use App\Models\Db\TimeTracker\Screen;
use App\Models\Db\User;
use App\Modules\TimeTracker\Models\Contracts\IScreenPaths;
use App\Modules\TimeTracker\Models\Contracts\IScreenDBSaver;
use Illuminate\Contracts\Auth\Guard;

class ScreenDBSaver implements IScreenDBSaver
{
    private User $user;
    private Screen $screen;

    public function __construct(Screen $screen, Guard $guard)
    {
        $this->user = $guard->user();
        $this->screen = $screen;
    }

    public function saveScreen(IScreenPaths $screen_paths): bool
    {
        if (! $screen_paths->isValid()) {
            return false;
        }
        $this->screen = new Screen();
        $this->screen->user_id = $this->user->id;
        $this->screen->name = $screen_paths->getScreenName();
        $this->screen->url_link = $screen_paths->getStorageLinkUrl();
        $this->screen->thumbnail_link = $screen_paths->getStorageLinkThumb();

        return $this->screen->save();
    }
}
