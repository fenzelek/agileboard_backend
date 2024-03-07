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
use App\Modules\TimeTracker\Services\Screenshots;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ScreenshotsTest extends TestCase
{
    use DatabaseTransactions;
    use ScreenshotsTrait;

    /**
     * @var Screenshots
     */
    private $screenshots;
    /**
     * @var \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|mixed
     */
    private $own_user;
    private $own_company;
    private ?Project $project = null;

    protected function setUp(): void
    {
        parent::setUp();
        \Auth::shouldReceive('id')->andReturn(1);

        $this->screenshots = $this->app->make(Screenshots::class);
        $this->own_user = $this->createLocalUser();
        $this->own_company = $this->createCompany();
    }

    /**
     * @feature Time Tracker
     * @scenario Get Screenshots
     * @case Not User's screenshots found
     *
     * @test
     */
    public function get_not_screenshots_provided_when_only_other_user_screens_exist()
    {
        // GIVEN
        $other_user = $this->createLocalUser();
        $activity = $this->createActivityWithScreenshots($other_user, '2020-01-01', $this->own_company);

        // WHEN
        $query_data = $this->createScreenshotQueryData('2020-01-01');
        $screenshots = $this->screenshots->get($query_data);

        // THEN
        $this->assertEmpty($screenshots);
    }

    /**
     * @feature Time Tracker
     * @scenario Get Screenshots
     * @case Not User's screenshots found
     *
     * @test
     */
    public function get_not_screenshots_provided_when_screens_for_given_not_exist()
    {
        // GIVEN
        $one_day_before_selected_day = '2020-01-01';
        $selected_day = '2020-01-02';
        $activity = $this->createActivityWithScreenshots($this->own_user, $one_day_before_selected_day, $this->own_company);

        // WHEN
        $query_data = $this->createScreenshotQueryData($selected_day);
        $screenshots = $this->screenshots->get($query_data);

        // THEN
        $this->assertEmpty($screenshots);
    }

    /**
     * @feature Time Tracker
     * @scenario Get Screenshots
     * @case Not User's screenshots found
     *
     * @test
     */
    public function get_not_screenshots_provided_when_only_screens_belongs_to_other_company_exist()
    {
        // GIVEN
        $selected_day = '2020-01-02';
        $other_company = $this->createCompany();
        $this->createActivityWithScreenshots($this->own_user, $selected_day, $other_company);

        // WHEN
        $query_data = $this->createScreenshotQueryData($selected_day);
        $screenshots = $this->screenshots->get($query_data);

        // THEN
        $this->assertEmpty($screenshots);
    }

    /**
     * @feature Time Tracker
     * @scenario Get Screenshots
     * @case User's screenshots found
     *
     * @test
     */
    public function get_found_screenshots()
    {
        // GIVEN
        $selected_day = '2020-01-02';
        $activities = $this->createActivityWithScreenshots($this->own_user, $selected_day, $this->own_company);

        // WHEN
        $query_data = $this->createScreenshotQueryData($selected_day);
        $screenshots = $this->screenshots->get($query_data);

        // THEN
        $this->assertCount(1, $screenshots);
    }

    /**
     * @feature Time Tracker
     * @scenario Get Screenshots
     * @case User's screenshots found
     *
     * @test
     */
    public function get_activity_include_screens()
    {
        // GIVEN
        $selected_day = '2020-01-02';
        $activities = $this->createActivityWithScreenshots($this->own_user, $selected_day, $this->own_company);

        // WHEN
        $query_data = $this->createScreenshotQueryData($selected_day);
        $screenshots = $this->screenshots->get($query_data);

        // THEN
        $this->assertCount(1, $screenshots[0]->getScreens());
    }

    /**
     * @feature Time Tracker
     * @scenario Get Screenshots
     * @case User's screenshots found
     *
     * @test
     */
    public function get_activity_include_ticket()
    {
        // GIVEN
        $selected_day = '2020-01-02';
        $activities = $this->createActivityWithScreenshots($this->own_user, $selected_day, $this->own_company);

        // WHEN
        $query_data = $this->createScreenshotQueryData($selected_day);
        $screenshots = $this->screenshots->get($query_data);

        // THEN
        $this->assertNotEmpty($screenshots[0]->ticket);
    }

    /**
     * @feature Time Tracker
     * @scenario Get Screenshots
     * @case User's screenshots filter by project found
     *
     * @test
     */
    public function get_activity_filter_by_project_found()
    {
        // GIVEN
        $selected_day = '2020-01-02';
        $activities = $this->createActivityWithScreenshots($this->own_user, $selected_day, $this->own_company);
        $this->project = $this->createProject($this->own_company);

        // WHEN
        $query_data = $this->createScreenshotQueryData($selected_day);
        $screenshots = $this->screenshots->get($query_data);

        // THEN
        $this->assertTrue($screenshots->isEmpty());
    }

}
