<?php

namespace Tests\Feature\App\Modules\Integration\Http\Controllers\TimeTrackingActivityController;

use App\Helpers\ErrorCode;
use App\Models\Db\Company;
use App\Models\Db\Integration\Integration;
use App\Models\Db\Integration\IntegrationProvider;
use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\Project;
use App\Models\Db\Ticket;
use App\Models\Db\TimeTracker\ActivityFrameScreen;
use App\Models\Db\User;
use App\Models\Other\RoleType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Tests\BrowserKitTestCase;
use App\Models\Db\Integration\TimeTracking\User as TimeTrackingUser;
use Tests\Helpers\ProjectHelper;
use Tests\Helpers\ResponseHelper;
use Tests\TestCase;

class DailySummaryTest extends TestCase
{
    use DatabaseTransactions, ResponseHelper, ProjectHelper;

    /**
     * @var Company
     */
    protected $company;


    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $this->company = factory(Company::class)->create();

        $tracking_activities = $this->createActivityTimeTracker();
    }

    /** @test */
    public function it_gets_daily_summary()
    {
        //GIVEN
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);
        $start_at = '2022-01-01';
        $finished_at = '2022-01-02';

        //WHEN
        $response = $this->get('/integrations/time_tracking/activities/daily-summary/'.'?selected_company_id='.
            $this->company->id.'&started_at='.$start_at.'&finished_at='.$finished_at);

        $response->assertSuccessful();

        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'date',
                    'tracked'
                ]
            ]
        ]);
    }

    protected function createActivityTimeTracker():Activity
    {
        $project = factory(Project::class)->create([
            'company_id' => $this->company->id
        ]);
        \DB::table('time_tracking_activities')->whereRaw('1=1')->delete();
        /**
         * @var Activity $activity
         */
        $activity = factory(Activity::class)->create([
            'user_id' => $this->user->id,
            'utc_started_at' => Carbon::parse('2022-01-01')->toDateTimeString(),
            'utc_finished_at' => Carbon::parse('2022-01-01')->addHour()->toDateTimeString(),
            'tracked' => 1500,
        ]);
        $activity->project()->associate($project);
        $activity->save();
        return $activity;

    }
}
