<?php

namespace Tests\Feature\App\Modules\Integration\Services\TimeTracker;

use App\Models\Db\Company;
use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\Project;
use App\Models\Db\User;
use App\Modules\Integration\Services\Contracts\DailyActivityEntryData;
use Carbon\Carbon;
use Carbon\CarbonInterface;

trait TimeTrackerTrait
{

    protected function createActivityTimeTracker(string $started_at, string $finished_at, Project $project, User $user): Activity
    {
        /**
         * @var Activity $activity
         */
        $activity = factory(Activity::class)->create([
            'user_id' => $user->id,
            'utc_started_at' => Carbon::parse($started_at)->toDateTimeString(),
            'utc_finished_at' => Carbon::parse($finished_at)->toDateTimeString(),
            'tracked' => 1500,
        ]);
        $activity->project()->associate($project);
        $activity->save();

        return $activity;

    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|mixed
     */
    private function createProject(Company $company)
    {
        return factory(Project::class)->create([
            'company_id' => $company->id,
        ]);
    }

    private function createCompany(): Company
    {
        return factory(Company::class)->create();
    }

    private function entryData(string $started_at, string $finised_at, Company $company, User $user, int $time_zone_offset = 0): DailyActivityEntryData
    {
        return new class($started_at, $finised_at, $company, $user, $time_zone_offset) implements DailyActivityEntryData {

            private string $started_at;
            private string $finished_at;
            private Company $company;
            private User $user;
            private int $time_zone_offset;

            public function __construct(string $started_at, string $finished_at, Company $company, User $user, int $time_zone_offset)
            {
                $this->started_at = $started_at;
                $this->finished_at = $finished_at;
                $this->company = $company;
                $this->user = $user;
                $this->time_zone_offset = $time_zone_offset;
            }

            public function getUserId(): int
            {
                return $this->user->id;
            }

            public function getCompanyId(): int
            {
                return $this->company->id;
            }

            public function getStartedAt(): CarbonInterface
            {
                return Carbon::parse($this->started_at);
            }

            public function getFinishedAt(): CarbonInterface
            {
                return Carbon::parse($this->finished_at);
            }

            public function getTimeZoneOffset(): int
            {
                return $this->time_zone_offset;
            }
        };
    }

    private function createOtherUser(): User
    {
        return factory(User::class)->create();
    }

    public function provideFullOutsideEntryData(): iterable
    {
        return [
            [
                'started_at' => '2022-01-01 00:00:00',
                'finished_at' => '2022-01-01 01:00:00',
                'searchable_started_at' => '2022-01-01 00:00:00',
                'searchable_finished_at' => '2022-01-02 00:00:00',
                '$time_zone_offset' => 1,
            ],
            [
                'started_at' => '2022-01-01 23:00:00',
                'finished_at' => '2022-01-02 00:00:00',
                'searchable_started_at' => '2022-01-01 00:00:00',
                'searchable_finished_at' => '2022-01-02 00:00:00',
                '$time_zone_offset' => -1,
            ],
        ];
    }

    public function provideOutsideEntryData(): iterable
    {
        return [
            [
                'started_at' => '2022-01-01',
                'finished_at' => '2022-01-02',
                'searchable_started_at' => '2000-01-01',
                'searchable_finished_at' => '2000-01-01',
            ],
            [
                'started_at' => '2022-01-01',
                'finished_at' => '2022-01-02',
                'searchable_started_at' => '2022-11-01',
                'searchable_finished_at' => '2022-11-01',
            ],
        ];
    }

    public function provideInBoundEntryData(): iterable
    {
        return [
            [
                'started_at' => '2022-01-01',
                'finished_at' => '2022-01-02',
                'searchable_started_at' => '2022-01-01',
                'searchable_finished_at' => '2022-01-01',
            ],
            [
                'started_at' => '2022-01-01',
                'finished_at' => '2022-01-02',
                'searchable_started_at' => '2022-01-02',
                'searchable_finished_at' => '2022-11-01',
            ],
            [
                'started_at' => '2022-01-01',
                'finished_at' => '2022-01-02',
                'searchable_started_at' => '2000-01-02',
                'searchable_finished_at' => '2022-11-01',
            ],
        ];
    }
}
