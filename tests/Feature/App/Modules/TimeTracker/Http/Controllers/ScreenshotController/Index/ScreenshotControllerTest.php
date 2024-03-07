<?php

namespace Tests\Feature\App\Modules\TimeTracker\Http\Controllers\ScreenshotController\Index;

use App\Models\Db\Project;
use App\Models\Db\Ticket;
use App\Models\Db\User;
use App\Models\Other\RoleType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BrowserKitTestCase;

class ScreenshotControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions;
    use ScreenshotControllerTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createUser();
        $this->be($this->user);
    }

    /**
     * @feature Time Tracker
     * @scenario Get Screenshots
     * @case Block endpoint because user not Admin/Owner
     *
     * @test
     */
    public function index_block_endpoint_because_user_not_admin_or_owner()
    {
        // GIVEN
        $company = $this->prepareCompany(RoleType::DEVELOPER);
        $this->assignUsersToCompany(collect([$this->user]), $company);

        // WHEN
        $this->sendGetScreenshotsRequest(['selected_company_id' => $company->id]);

        //THEN
        $this->verifyErrorResponse(401);
    }

    /**
     * @feature Time Tracker
     * @scenario Get Screenshots
     * @case Missing required fields for sent request
     *
     * @test
     */
    public function index_screenshots_missing_required_parameters()
    {
        // GIVEN
        $company = $this->prepareCompany(RoleType::OWNER);
        $this->assignUsersToCompany(collect([$this->user]), $company, RoleType::ADMIN);

        // WHEN
        $this->sendGetScreenshotsRequest(['selected_company_id' => $company->id]);

        //THEN
        $this->verifyValidationResponse([
            'user_id',
            'date',
        ]);
    }

    /**
     * @feature Time Tracker
     * @scenario Get Screenshots
     * @case Not User's screenshots found
     *
     * @test
     */
    public function index_not_users_screenshots_found()
    {
        // GIVEN
        $company = $this->prepareCompany(RoleType::OWNER);
        $this->assignUsersToCompany(collect([$this->user]), $company, RoleType::ADMIN);

        // WHEN
        $entry = [
            'user_id' => $this->user->id,
            'date' => '2020-01-01',
            'selected_company_id' => $company->id,
        ];

        $response = $this->sendGetScreenshotsRequest($entry);

        //THEN
        $this->assertResponseOk();
        $activities = $response->decodeResponseJson();
        $this->assertSame([], $activities['data']);
    }

    /**
     * @feature Time Tracker
     * @scenario Get Screenshots
     * @case Filter By Project
     *
     * @test
     * @expectation Provide Project not found
     */
    public function index_filter_by_project_which_not_found()
    {
        // GIVEN
        $company = $this->prepareCompany(RoleType::OWNER);
        $this->assignUsersToCompany(collect([$this->user]), $company, RoleType::ADMIN);
        $project = factory(Project::class)->create();
        $project_id = $project->id;
        \DB::table('projects')->whereId($project_id)->delete();

        // WHEN
        $entry = [
            'user_id' => $this->user->id,
            'date' => '2020-01-01',
            'selected_company_id' => $company->id,
            'project_id' => $project_id
        ];

        $response = $this->sendGetScreenshotsRequest($entry);

        //THEN
        $this->verifyValidationResponse([
            'project_id'
        ]);
    }

    /**
     * @feature Time Tracker
     * @scenario Get Screenshots
     * @case User's screenshots found
     *
     * @test
     */
    public function index_users_screenshots_found()
    {
        // GIVEN
        $selected_date = '2020-01-01';
        $company = $this->prepareCompany(RoleType::OWNER);
        $this->assignUsersToCompany(collect([$this->user]), $company, RoleType::ADMIN);
        $activity = $this->createActivity($this->user, $company, $selected_date);
        $screen = $this->createScreenshot($this->user, $activity);

        // WHEN
        $entry = [
            'user_id' => $this->user->id,
            'date' => $selected_date,
            'selected_company_id' => $company->id,
        ];

        $response = $this->sendGetScreenshotsRequest($entry);

        //THEN
        $activities = $response->decodeResponseJson();
        $this->assertSame([
            [
                'id' => $activity->id,
                'utc_started_at' => $activity->utc_started_at->toDateTimeString(),
                'utc_finished_at' => $activity->utc_finished_at->toDateTimeString(),
                'tracked' => $activity->tracked,
                'activity' => $activity->activity,
                'activity_level' => $activity->activity_level,
                'comment' => $activity->comment,
                'ticket' => null,
                'project_name' => $activity->project->name,
                'screens' => [
                    [
                        'thumb' => config('filesystems.disks.azure.url') . $screen->thumbnail_link,
                        'url' => config('filesystems.disks.azure.url') . $screen->url_link,
                    ],
                ],
            ],
        ], $activities['data']);
    }

    private function sendGetScreenshotsRequest(array $entry):self
    {
        return $this->get(route('time-tracker.screenshots.index', $entry), []);
    }
}
