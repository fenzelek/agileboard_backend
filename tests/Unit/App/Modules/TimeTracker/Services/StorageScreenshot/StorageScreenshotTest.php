<?php

namespace Tests\Unit\App\Modules\TimeTracker\Services\StorageScreenshot;

use App\Models\Db\Project;
use App\Modules\TimeTracker\Models\ScreenPaths;
use App\Modules\TimeTracker\Services\PathGenerator;
use App\Modules\TimeTracker\Services\ScreenDBSaver;
use App\Modules\TimeTracker\Services\StorageScreenshot;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorageScreenshotTest extends TestCase
{
    use DatabaseTransactions;
    use StorageScreenTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $conf = $this->app->make(ConfigContract::class);

        $this->disk = $conf->get('image.disk');
        $this->main_folder = $conf->get('image.folder_main');
    }

    /**
     * @feature Time Tracker
     * @scenario Add screenshot
     * @case success
     *
     * @test
     */
    public function add_screenshot_success()
    {
        //GIVEN
        Storage::fake('azure');
        $this->createUser();
        $company = $this->prepareCompany();
        $this->assignUsersToCompany(collect([$this->user]), $company);
        $this->be($this->user);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $project->users()->attach($this->user);

        $name = '1_1_123456789_1.jpeg';
        $screen_files_provider = $this->getScreenFilesProvider($project, $name);

        $screen_service = $this->app->make(ScreenDBSaver::class);
        $tested_method = $this->app->make(StorageScreenshot::class);

        //WHEN
        $response = $tested_method->addScreenshot($screen_files_provider, $screen_service);

        //THEN
        $this->assertIsBool($response);
        $this->assertTrue($response);
        Storage::disk($this->disk)->deleteDirectory($this->main_folder);
        $this->assertDatabaseHas('time_tracker_screens', [
            'name' => $name,
            'user_id' => $this->user->id,
        ]);
    }

    /**
     * @feature Time Tracker
     * @scenario Add screenshot
     * @case add failed, storage error
     *
     * @test
     */
    public function add_screenshot_failed()
    {
        //GIVEN
        Storage::fake('azure');
        Storage::shouldReceive('disk')->once()->andThrow(\Exception::class);

        $this->createUser();
        $company = $this->prepareCompany();
        $this->assignUsersToCompany(collect([$this->user]), $company);
        $this->be($this->user);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $project->users()->attach($this->user);

        $name = '1_1_123456789_1.jpeg';
        $screen_files_provider = $this->getScreenFilesProvider($project, $name);

        $screen_paths = new ScreenPaths();
        $screen_paths->setValid(true);
        $screen_paths->setScreenName('name.jpeg');

        $builder = \Mockery::mock(PathGenerator::class);
        $builder->shouldReceive('pathBuilder')->andReturn($screen_paths);

        $screen_service = $this->app->make(ScreenDBSaver::class);
        $tested_method = $this->app->make(StorageScreenshot::class, ['builder' => $builder]);

        //WHEN
        $response = $tested_method->addScreenshot($screen_files_provider, $screen_service);

        //THEN
        $this->assertIsBool($response);
        $this->assertFalse($response);
    }
}
