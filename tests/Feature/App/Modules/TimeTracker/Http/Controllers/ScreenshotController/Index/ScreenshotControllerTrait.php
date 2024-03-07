<?php

namespace Tests\Feature\App\Modules\TimeTracker\Http\Controllers\ScreenshotController\Index;

use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\Package;
use App\Models\Db\Project;
use App\Models\Db\Role;
use App\Models\Db\TimeTracker\ActivityFrameScreen;
use App\Models\Db\TimeTracker\Screen;
use App\Models\Db\User;

trait ScreenshotControllerTrait
{
    protected function prepareCompany($role)
    {
        $company = $this->createCompanyWithRoleAndPackage($role, Package::CEP_FREE);
        $company->roles()->attach(Role::findByName($role)->id);

        return $company;
    }

    protected function createScreenshot(User $user, Activity $activity): Screen
    {
        /**
         * @var Screen $screen
         */
        $screen = factory(Screen::class)->make();
        $screen->user()->associate($user);
        $screen->save();

        $pivot = new ActivityFrameScreen();
        $pivot->screen()->associate($screen);
        $activity->screens()->save($pivot);

        return $screen;
    }

    protected function createActivity(User $user, $company, $selected_date): Activity
    {
        $project = factory(Project::class)->create([
            'company_id' => $company->id,
        ]);
        /**
         * @var Activity $activity
         */
        $activity = factory(Activity::class)->make();
        $activity->utc_started_at = $selected_date;
        $activity->utc_finished_at = $selected_date;
        $activity->project()->associate($project);
        $activity->user()->associate($user);
        $activity->save();

        return $activity;
    }
}
