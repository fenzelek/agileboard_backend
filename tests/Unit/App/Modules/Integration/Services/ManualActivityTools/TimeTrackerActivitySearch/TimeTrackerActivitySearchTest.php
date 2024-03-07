<?php

namespace Tests\Unit\App\Modules\Integration\Services\ManualActivityTools\TimeTrackerActivitySearch;

use App\Models\Db\Company;
use App\Models\Db\Project;
use App\Models\Db\Ticket;
use App\Models\Db\User;
use App\Models\Other\RoleType;
use App\Modules\Integration\Services\ManualActivityTools\TimeTrackerActivitySearch;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Unit\App\Modules\Integration\Services\ManualActivityTools\ManualActivityToolsTrait;

class TimeTrackerActivitySearchTest extends TestCase
{
    use DatabaseTransactions;
    use ManualActivityToolsTrait;

    /**
     * @var Company
     */
    private $company;

    /**
     * @var Project
     */
    private $project;

    /**
     * @var Project
     */
    private $other_project;

    /**
     * @var Ticket
     */
    private $ticket;

    /**
     * @var User
     */
    private $other_user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $this->company = factory(Company::class)->create();
        $this->project = $this->getProject($this->company, $this->user->id, RoleType::DEVELOPER);
        $this->ticket = $this->getTicket($this->project);

        $this->other_user = factory(User::class)->create();
        $this->other_project =
            $this->getProject($this->company, $this->other_user, RoleType::DEVELOPER);

        $this->activity_searcher = $this->app->make(TimeTrackerActivitySearch::class);
    }

    /**
     * @feature Manual Activity
     * @scenario Activity Search
     * @case zero activities in DB
     *
     * @test
     */
    public function lookup_overlap_zero_found_activities()
    {
        //GIVEN
        $store_activity = $this->getStoreActivity('2021-10-01 11:00:00', '2021-10-01 11:10:00');

        //WHEN
        $response = $this->activity_searcher->lookupOverLap($store_activity);

        //THEN
        $this->assertCount(0, $response);
    }

    /**
     * @feature Manual Activity
     * @scenario Activity Search
     * @case one activity in DB, in the same, a new activity
     *
     * @test
     */
    public function lookup_overlap_one_same_activity_found()
    {
        //GIVEN
        $store_activity = $this->getStoreActivity();
        $this->createDBActivity(
            $this->user,
            '2021-10-01 11:00:00',
            '2021-10-01 11:10:00',
            $this->project,
            $this->ticket
        );

        //WHEN
        $response = $this->activity_searcher->lookupOverLap($store_activity);

        //THEN
        $this->assertCount(1, $response);
    }

    /**
     * @feature Manual Activity
     * @scenario Activity Search
     * @case Existing activity not found during adding activity
     *
     * @test
     */
    public function lookup_overlap_skip_other_user_activity()
    {
        //GIVEN
        $store_activity = $this->getStoreActivity('2021-10-01 11:00:00', '2021-10-01 11:10:00');
        $this->createDBActivity(
            $this->other_user,
            '2021-10-01 11:00:00',
            '2021-10-01 11:10:00',
            $this->other_project,
            $this->ticket
        );

        //WHEN
        $response = $this->activity_searcher->lookupOverLap($store_activity);

        //THEN
        $this->assertCount(0, $response);
    }

    /**
     * @feature Manual Activity
     * @scenario Activity Search
     * @case Existing activity found during adding activity, Db has many others activities
     *
     * @test
     */
    public function lookup_overlap_found_one_user_activity()
    {
        //GIVEN
        $store_activity = $this->getStoreActivity('2021-10-01 11:00:00', '2021-10-01 11:10:00');
        $this->createDBActivity(
            $this->user,
            '2021-10-01 11:10:00',
            '2021-10-01 11:20:00',
            $this->project,
            $this->ticket
        );
        $this->createDBActivity(
            $this->other_user,
            '2021-10-01 11:00:00',
            '2021-10-01 11:10:00',
            $this->other_project,
            $this->ticket
        );
        $this->createDBActivity(
            $this->other_user,
            '2021-10-01 11:10:00',
            '2021-10-01 11:20:00',
            $this->other_project,
            $this->ticket
        );

        //WHEN
        $response = $this->activity_searcher->lookupOverLap($store_activity);

        //THEN
        $this->assertCount(1, $response);
    }

    /**
     * @feature Manual Activity
     * @scenario Activity Search
     * @case Existing activity found during adding activity, Db has three current activities
     *
     * @test
     */
    public function lookup_overlap_found_three_user_activity()
    {
        //GIVEN
        $store_activity = $this->getStoreActivity('2021-10-01 11:00:00', '2021-10-01 11:10:00');
        $this->createDBActivity(
            $this->user,
            '2021-10-01 11:10:00',
            '2021-10-01 11:20:00',
            $this->project,
            $this->ticket
        );
        $this->createDBActivity(
            $this->user,
            '2021-10-01 11:02:00',
            '2021-10-01 11:08:00',
            $this->project,
            $this->ticket
        );
        $this->createDBActivity(
            $this->user,
            '2021-10-01 10:50:00',
            '2021-10-01 11:00:00',
            $this->project,
            $this->ticket
        );

        //WHEN
        $response = $this->activity_searcher->lookupOverLap($store_activity);

        //THEN
        $this->assertCount(3, $response);
    }

    /**
     * @feature Manual Activity
     * @scenario Activity Search
     * @case Existing activity found during adding activity, Db has three current activities,
     *     different project
     *
     * @test
     */
    public function lookup_overlap_found_two_user_activity_diff_project()
    {
        //GIVEN
        $store_activity = $this->getStoreActivity('2021-10-01 11:00:00', '2021-10-01 11:10:00');

        $this->createDBActivity(
            $this->user,
            '2021-10-01 11:02:00',
            '2021-10-01 11:08:00',
            $this->project,
            $this->ticket
        );
        $this->createDBActivity(
            $this->user,
            '2021-10-01 10:58:00',
            '2021-10-01 11:12:00',
            $this->other_project,
            $this->ticket
        );

        //WHEN
        $response = $this->activity_searcher->lookupOverLap($store_activity);

        //THEN
        $this->assertCount(2, $response);
    }
}
