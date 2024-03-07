<?php

namespace Tests\Feature\App\Modules\Integration\Services\TimeTracker;

use App\Modules\Integration\Services\TimeTracker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Helpers\ProjectHelper;
use Tests\Helpers\ResponseHelper;
use Tests\TestCase;

class TimeTrackerTest extends TestCase
{
    use DatabaseTransactions;
    use ResponseHelper;
    use ProjectHelper;
    use TimeTrackerTrait;

    private TimeTracker $service;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        \DB::table('time_tracking_activities')->whereRaw('1=1')->delete();
        $this->service = $this->app->make(TimeTracker::class);
    }

    /**
     * @test
     *
     * @Expectation Skip other user activities
     * Skip outside searchable period activities
     */
    public function gtTimeSummary_whenOnlyOtherCompanyExisting_notFoundActivities()
    {
        //GIVEN
        $start_at = '2022-01-01';
        $finished_at = '2022-01-02';
        $company = $this->createCompany();
        $other_company = $this->createCompany();
        $project = $this->createProject($other_company);
        $this->createUser();
        $this->createActivityTimeTracker($start_at, $finished_at, $project, $this->user);

        $entry_data = $this->entryData($start_at, $finished_at, $company, $this->user);

        //WHEN
        $results = $this->service->getTimeSummary($entry_data);

        //THEN
        $this->assertCount(0, $results);
    }

    /**
     * @test
     *
     * @Expectation
     * Skip outside searchable period activities
     * @dataProvider provideFullOutsideEntryData
     */
    public function gtTimeSummary_ActivityWithOffset_notFoundActivities(
        $activity_started_at, $activity_finished_at, $search_started_at, $search_finished_at, $time_zone_offset
    ) {
        //GIVEN
        $company = $this->createCompany();
        $project = $this->createProject($company);
        $this->createUser();

        $this->createActivityTimeTracker($activity_started_at, $activity_finished_at, $project,
            $this->user);

        $entry_data = $this->entryData(
            $search_started_at,
            $search_finished_at,
            $company,
            $this->user,
            $time_zone_offset
        );

        //WHEN
        $results = $this->service->getTimeSummary($entry_data);

        //THEN
        $this->assertCount(0, $results);
    }

    /**
     * @test
     *
     * @Expectation Skip other user activities
     */
    public function gtTimeSummary_whenOnlyOtherUserExisting_notFoundActivities()
    {
        //GIVEN
        $start_at = '2022-01-01';
        $finished_at = '2022-01-02';
        $company = $this->createCompany();
        $project = $this->createProject($company);
        $this->createUser();
        $other_user = $this->createOtherUser();
        $this->createActivityTimeTracker($start_at, $finished_at, $project, $this->user);

        $entry_data = $this->entryData($start_at, $finished_at, $company, $other_user);

        //WHEN
        $results = $this->service->getTimeSummary($entry_data);

        //THEN
        $this->assertCount(0, $results);
    }

    /**
     * @test
     *
     * @Expectation Skip outside searchable period activities
     * @dataProvider provideOutsideEntryData
     */
    public function gtTimeSummary_whenOnlyOutsideSearchableActivities_notFoundActivities($started_at, $finished_at, $searchable_started_at, $searchable_finished_at)
    {
        //GIVEN
        $company = $this->createCompany();
        $project = $this->createProject($company);
        $this->createUser();
        $other_user = $this->createOtherUser();
        $this->createActivityTimeTracker($started_at, $finished_at, $project, $this->user);
        $entry_data = $this->entryData(
            $searchable_started_at,
            $searchable_finished_at,
            $company,
            $this->user
        );

        //WHEN
        $results = $this->service->getTimeSummary($entry_data);

        //THEN
        $this->assertCount(0, $results);
    }

    /**
     * @test
     *
     * @Expectation
     * Skip outside searchable period activities
     * @dataProvider provideInBoundEntryData
     */
    public function gtTimeSummary_FoundActivities($started_at, $finished_at, $searchable_started_at, $searchable_finished_at)
    {
        //GIVEN
        $this->markTestSkipped('Obsolete');
        $company = $this->createCompany();
        $project = $this->createProject($company);
        $this->createUser();
        $this->createActivityTimeTracker($started_at, $finished_at, $project, $this->user);

        $entry_data = $this->entryData(
            $searchable_started_at,
            $searchable_finished_at,
            $company,
            $this->user
        );

        //WHEN
        $results = $this->service->getTimeSummary($entry_data);

        //THEN
        $this->assertCount(1, $results);
    }

    /**
     * @test
     *
     * @Expectation Sum many activities in searchable period
     */
    public function getTimeSummary_FoundTwoActivities()
    {
        //GIVEN
        $started_at = $finished_at = $searchable_started_at = $searchable_finished_at = '2022-01-01';
        $company = $this->createCompany();
        $project = $this->createProject($company);
        $this->createUser();
        $this->createActivityTimeTracker($started_at, $finished_at, $project, $this->user);
        $this->createActivityTimeTracker($started_at, $finished_at, $project, $this->user);

        $entry_data = $this->entryData(
            $searchable_started_at,
            $searchable_finished_at,
            $company,
            $this->user
        );

        //WHEN
        $results = $this->service->getTimeSummary($entry_data);

        //THEN
        $this->assertCount(1, $results);
        $this->assertEquals(2 * 1500, $results[0]->tracked);
        $this->assertSame($searchable_started_at, $results[0]->date);
    }

    /**
     * @test
     *
     * @Expectation Skip Trushed Activities
     */
    public function getTimeSummary_whenOnlyTrashedFramesExising_NotFoundActivities()
    {
        //GIVEN
        $started_at =
        $finished_at = $searchable_started_at = $searchable_finished_at = '2022-01-01';
        $company = $this->createCompany();
        $project = $this->createProject($company);
        $this->createUser();
        $activity =
            $this->createActivityTimeTracker($started_at, $finished_at, $project, $this->user);
        $activity->delete();

        $entry_data = $this->entryData(
            $searchable_started_at,
            $searchable_finished_at,
            $company,
            $this->user
        );

        //WHEN
        $results = $this->service->getTimeSummary($entry_data);

        //THEN
        $this->assertCount(0, $results);
    }
}
