<?php

namespace Tests\Feature\App\Modules\TimeTracker\Http\Controllers\ScreenshotController\IndexOwn;

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
            'date',
        ]);
    }

    /**
     * @feature Time Tracker
     * @scenario Get Screenshots
     * @case Not Own screenshots found
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
     * @case Own screenshots found
     *
     * @test
     */
    public function index_users_screenshots_found()
    {
        // GIVEN
        $selected_date = '2020-01-01';
        $company = $this->prepareCompany(RoleType::DEVELOPER);
        $this->assignUsersToCompany(collect([$this->user]), $company, RoleType::DEVELOPER);
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
        return $this->get(route('time-tracker.screenshots.own', $entry), []);
    }
}
