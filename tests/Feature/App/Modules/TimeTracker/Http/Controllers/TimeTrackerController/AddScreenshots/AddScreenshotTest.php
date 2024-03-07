<?php

namespace Tests\Feature\App\Modules\TimeTracker\Http\Controllers\TimeTrackerController\AddScreenshots;

use App\Models\Db\Project;
use App\Models\Db\TimeTracker\Screen;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\BrowserKitTestCase;

class AddScreenshotTest extends BrowserKitTestCase
{
    use DatabaseTransactions;
    use AddScreenshotTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $conf = $this->app->make(ConfigContract::class);
        $this->main_folder = $conf->get('image.folder_main');
    }

    /**
     * @feature Time Tracker
     * @scenario Add frame
     * @case Check if sent files was properly added on server
     *
     * @test
     * @covers \App\Modules\TimeTracker\Http\Controllers\TimeTrackerController::addScreenshots
     */
    public function addScreenshots_check_if_sent_files_was_properly_added_on_server_jpeg()
    {
        // GIVEN
        Storage::fake('azure');
        $this->createUser();
        $company = $this->prepareCompany();
        $this->assignUsersToCompany(collect([$this->user]), $company);
        $this->be($this->user);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $project->users()->attach($this->user);

        $upload_file = UploadedFile::fake()->image('screen.jpeg', 3200, 2400);
        $entry_data = [
            'screen' => $upload_file,
            'screen_id' => '12_12_123456789_1.jpeg',
            'project_id' => $project->id,
        ];

        $path_thumb = $this->getPath($project, $company, 'thumb');
        $path_url = $this->getPath($project, $company, 'url');

        // WHEN
        $response = $this->json('POST', route('time-tracker.add-screenshots'), $entry_data);
        $screen = Screen::first();

        // THEN
        $this->assertResponseStatus(200);
        $this->assertSame($this->user->id, $screen->user_id);
        $this->assertSame('12_12_123456789_1.jpeg', $screen->name);
        Storage::disk('azure')->assertExists($path_thumb . '12_12_123456789_1.jpeg');
        Storage::disk('azure')->assertExists($path_url . '12_12_123456789_1.jpeg');
    }

    /**
     * @feature Time Tracker
     * @scenario Add frame
     * @case Check if sent files was properly added on server
     *
     * @test
     * @covers \App\Modules\TimeTracker\Http\Controllers\TimeTrackerController::addScreenshots
     */
    public function addScreenshots_check_if_sent_files_was_properly_added_on_server_png()
    {
        // GIVEN
        Storage::fake('azure');
        $this->createUser();
        $company = $this->prepareCompany();
        $this->assignUsersToCompany(collect([$this->user]), $company);
        $this->be($this->user);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $project->users()->attach($this->user);

        $path_thumb = $this->getPath($project, $company, 'thumb');
        $path_url = $this->getPath($project, $company, 'url');

        $upload_file = UploadedFile::fake()->image('screen.png');
        $entry_data = [
            'screen' => $upload_file,
            'screen_id' => '1_1_123456789_1.png',
            'project_id' => $project->id,
        ];

        // WHEN
        $this->json('POST', route('time-tracker.add-screenshots'), $entry_data);

        // THEN
        $this->assertResponseStatus(200);
        Storage::disk('azure')->assertExists($path_thumb . '1_1_123456789_1.png');
        Storage::disk('azure')->assertExists($path_url . '1_1_123456789_1.png');
    }

//    /**
//     * @feature Time Tracker
//     * @scenario Add frame
//     * @case Add failed, storage error
//     *
//     * @test
//     * @covers \App\Modules\TimeTracker\Http\Controllers\TimeTrackerController::addScreenshots
//     */
//    public function addScreenshots_storage_error()
//    {
//        // GIVEN
//        Storage::fake('time-tracker');
//        Storage::shouldReceive('put')->andThrow(\Exception::class);
//        $this->createUser();
//        $company = $this->prepareCompany();
//        $this->assignUsersToCompany(collect([$this->user]), $company);
//        $this->be($this->user);
//        $project = factory(Project::class)->create(['company_id' => $company->id]);
//        $project->users()->attach($this->user);
//
//        $path_thumb = $this->getPath($project, $company, 'thumb');
//        $path_url = $this->getPath($project, $company, 'url');
//
//        $upload_file = UploadedFile::fake()->image('screen.png');
//        $entry_data = [
//            'screen' => $upload_file,
//            'screen_id' => '1_1_123456789_1.png',
//            'project_id' => $project->id,
//        ];
//
//        // WHEN
//        $this->json('POST', route('time-tracker.add-screenshots'), $entry_data);
//
//        // THEN
//        $this->assertResponseStatus(424);
//    }

    /**
     * @feature Time Tracker
     * @scenario Add screenshots
     * @case Return 401 when user is not authorized
     *
     * @test
     * @covers \App\Modules\TimeTracker\Http\Controllers\TimeTrackerController::addScreenshots
     */
    public function addScreenshots_return_401_when_user_is_not_authorized()
    {
        // GIVEN
        $this->createUser();
        $company = $this->prepareCompany();

        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $project->users()->attach($this->user);

        // WHEN
        $response = $this->json(
            'POST',
            route('time-tracker.add-screenshots', ['project' => $project->id]),
            []
        );

        //THEN
        $this->assertResponseStatus(401);
    }

    /**
     * @feature Time Tracker
     * @scenario Add screenshots
     * @case Show validation error for fields that are required and missing in request
     *
     * @test
     * @covers \App\Modules\TimeTracker\Http\Controllers\TimeTrackerController::addScreenshots
     */
    public function addScreenshots_show_validation_error_for_fields_that_are_required_and_missing_in_request()
    {
        // GIVEN
        $this->createUser();
        $company = $this->prepareCompany();
        $this->assignUsersToCompany(collect([$this->user]), $company);
        $this->be($this->user);

        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $project->users()->attach($this->user);

        // WHEN
        $response = $this->json(
            'POST',
            route('time-tracker.add-screenshots', ['project' => $project->id]),
            []
        );

        // THEN
        $this->verifyValidationResponse([
            'screen',
            'screen_id',
        ]);
    }

    /**
     * @feature Time Tracker
     * @scenario Add screenshots
     * @case Show validation error when send screenshots are not valid
     *
     * @test
     * @covers \App\Modules\TimeTracker\Http\Controllers\TimeTrackerController::addScreenshots
     */
    public function addScreenshots_show_validation_error_when_send_screenshots_are_not_valid()
    {
        // GIVEN
        $this->createUser();
        $company = $this->prepareCompany();
        $this->assignUsersToCompany(collect([$this->user]), $company);
        $this->be($this->user);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $project->users()->attach($this->user);

        $upload_file = UploadedFile::fake()->image('logo.txt');
        $entry_data = [
            'screen' => $upload_file,
            'screen_id' => '12_12_123456789_1.jpeg',
        ];

        // WHEN
        $response = $this->json(
            'POST',
            route('time-tracker.add-screenshots', ['project' => $project->id]),
            $entry_data
        );

        // THEN
        $this->assertResponseStatus(422);
    }

    /**
     * @feature Time Tracker
     * @scenario Add frame
     * @case Check if sent files was properly added on server
     *
     * @test
     * @covers \App\Modules\TimeTracker\Http\Controllers\TimeTrackerController::addScreenshots
     */
    public function addScreenshots_show_validation_error_when_send_wrong_project_id()
    {
        // GIVEN
        $this->createUser();
        $company = $this->prepareCompany();
        $this->assignUsersToCompany(collect([$this->user]), $company);
        $this->be($this->user);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $project->users()->attach($this->user);

        $this->getPath($project, $company, 'thumb/');
        $this->getPath($project, $company, 'url/');

        $upload_file = UploadedFile::fake()->image('screen.jpeg');
        $entry_data = [
            'screen' => $upload_file,
            'screen_id' => '12_12_123456789_1.jpeg',
            'project_id' => -456,
        ];

        // WHEN
        $this->json('POST', route('time-tracker.add-screenshots'), $entry_data);

        // THEN
        $this->assertResponseStatus(422);
    }

    /**
     * @feature Time Tracker
     * @scenario Add frame
     * @case Check if sent files was properly added on server
     *
     * @test
     * @covers \App\Modules\TimeTracker\Http\Controllers\TimeTrackerController::addScreenshots
     */
    public function addScreenshots_show_validation_error_when_send_different_project_id()
    {
        // GIVEN
        $this->createUser();
        $company = $this->prepareCompany();
        $this->assignUsersToCompany(collect([$this->user]), $company);
        $this->be($this->user);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $project->users()->attach($this->user);

        $different_project = factory(Project::class)->create(['company_id' => $company->id]);

        $upload_file = UploadedFile::fake()->image('screen.jpeg');
        $entry_data = [
            'screen' => $upload_file,
            'screen_id' => '12_12_123456789_1.jpeg',
            'project_id' => $different_project->id,
        ];

        // WHEN
        $this->json('POST', route('time-tracker.add-screenshots'), $entry_data);

        // THEN
        $this->assertResponseStatus(422);
    }

    /**
     * @feature Time Tracker
     * @scenario Add screenshots
     * @case Show validation error when send screenshots are not valid
     *
     * @test
     * @covers \App\Modules\TimeTracker\Http\Controllers\TimeTrackerController::addScreenshots
     */
    public function addScreenshots_show_validation_error_when_send_screenshots_are_not_valid_screen_id()
    {
        // GIVEN
        $this->createUser();
        $company = $this->prepareCompany();
        $this->assignUsersToCompany(collect([$this->user]), $company);
        $this->be($this->user);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $project->users()->attach($this->user);

        $upload_file = UploadedFile::fake()->image('logo.jpeg');
        $entry_data = [
            'screen' => $upload_file,
            'screen_id' => Str::random(256),
        ];

        // WHEN
        $response = $this->json(
            'POST',
            route('time-tracker.add-screenshots', ['project' => $project->id]),
            $entry_data
        );

        // THEN
        $this->assertResponseStatus(422);
    }
}
