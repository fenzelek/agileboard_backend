<?php

namespace Tests\Unit\App\Modules\TimeTracker\Services\Screenshots;

use App\Models\Db\Company;
use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\Project;
use App\Models\Db\Ticket;
use App\Models\Db\TimeTracker\ActivityFrameScreen;
use App\Models\Db\TimeTracker\Screen;
use App\Models\Db\User;
use App\Modules\TimeTracker\Http\Requests\Contracts\GetScreenshotsQueryData;

trait ScreenshotsTrait
{
    /**
     * @feature Time Tracker
     * @scenario Get Screenshots
     * @case Not User's screenshots found
     *
     * @test
     */
    public function get_not_found_screenshots()
    {
        // GIVEN

        // WHEN
        $query_data = $this->createScreenshotQueryData('2020-01-01');
        $screenshots = $this->screenshots->get($query_data);

        // THEN
        $this->assertEmpty($screenshots);
    }

    protected function prepareCompany()
    {
        $company = factory(Company::class)->create();
        $company->users()->attach($this->user->id);

        return $company;
    }

    protected function prepareProject($company)
    {
        $project = factory(Project::class)->create([
            'company_id' => $company->id,
        ]);
        $this->user->projects()->attach($project->id);

        return $project;
    }

    protected function makeActivity(): Activity
    {
        return factory(Activity::class)->make();
    }

    protected function createTicket(): Ticket
    {
        return factory(Ticket::class)->create();
    }

    protected function createActivityWithScreenshots(User $user, string $date, Company $company): Activity
    {
        $activity = $this->createActivity($user, $date, $company);
        $screen = $this->createScreen($user);
        $activity_screen_pivot = new ActivityFrameScreen();
        $activity_screen_pivot->screen()->associate($screen);
        $activity->screens()->save($activity_screen_pivot);

        return $activity;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|mixed
     */
    protected function createLocalUser()
    {
        return factory(User::class)->create();
    }

    protected function createScreenshotQueryData(string $selected_date): GetScreenshotsQueryData
    {
        return new class($this->own_user, $selected_date, $this->own_company, $this->project) implements GetScreenshotsQueryData {
            private User $user;
            private string $date;
            private Company $company;
            private ?Project $project;

            public function __construct(User $user, string $date, Company $company, ?Project $project)
            {
                $this->user = $user;
                $this->date = $date;
                $this->company = $company;
                $this->project = $project;
            }

            public function getDate(): string
            {
                return $this->date;
            }

            public function getUserId(): int
            {
                return $this->user->id;
            }

            public function getSelectedCompanyId(): int
            {
                return $this->company->getCompanyId();
            }

            public function getProjectId(): ?int
            {
                return optional($this->project)->id;
            }
        };
    }

    /**
     * @param User $user
     * @return Activity
     */
    protected function createActivity(User $user, string $date, Company $company): Activity
    {
        $project = $this->createProject($company);
        $ticket = $this->createTicket();
        $activity = $this->makeActivity();
        $activity->utc_started_at = $date;
        $activity->utc_finished_at = $date;
        $activity->project()->associate($project);
        $activity->user()->associate($user);
        $activity->ticket()->associate($ticket);
        $activity->save();

        return $activity;
    }

    protected function createCompany(): Company
    {
        return factory(Company::class)->create();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|mixed
     */
    protected function createScreen(User $user)
    {
        /**
         * @var $screen Screen
         */
        $screen = factory(Screen::class)->make();
        $screen->user()->associate($user);
        $screen->save();

        return $screen;
    }

    protected function createProject(Company $company): Project
    {
        $project = factory(Project::class)->make();
        $company->projects()->save($project);

        return $project;
    }
}
