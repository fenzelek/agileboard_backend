<?php

namespace Tests\Unit\App\Modules\TimeTracker\Services\ScreenService;

use App\Models\Db\Project;
use App\Modules\TimeTracker\Services\ScreenDBSaver;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SaveScreenTest extends TestCase
{
    use DatabaseTransactions;
    use SaveScreenTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $conf = $this->app->make(ConfigContract::class);

        $this->disk = $conf->get('image.disk');
        $this->main_folder = $conf->get('image.folder_main');
        Storage::fake($this->disk);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Storage::disk($this->disk)->deleteDirectory($this->main_folder);
    }

    /**
     * @feature Time Tracker
     * @scenario Add screenshot
     * @case success, save screen
     *
     * @test
     */
    public function success_save_screen()
    {
        //GIVEN
        $this->createUser();
        $company = $this->prepareCompany();
        $this->assignUsersToCompany(collect([$this->user]), $company);
        $this->be($this->user);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $project->users()->attach($this->user);

        $name = '1_1_123456789_1.jpeg';
        $screen_files_provider = $this->getScreenFilesProvider($project, $name);

        $screen_paths = $this->PathsGenerate($screen_files_provider);

        $tested_method = $this->app->make(ScreenDBSaver::class);

        //WHEN
        $response = $tested_method->saveScreen($screen_paths);

        //THEN
        $this->assertIsBool($response);
        $this->assertTrue($response);

        $this->assertDatabaseHas('time_tracker_screens', [
            'name' => $name,
            'user_id' => $this->user->id,
        ]);
    }

    /**
     * @feature Time Tracker
     * @scenario Add screenshot
     * @case failed, save screen
     *
     * @test
     */
    public function failed_save_screen()
    {
        //GIVEN
        $this->createUser();
        $company = $this->prepareCompany();
        $this->assignUsersToCompany(collect([$this->user]), $company);
        $this->be($this->user);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $project->users()->attach($this->user);

        $name = '1_1_123456789_1.jpeg';
        $screen_files_provider = $this->getScreenFilesProvider($project, $name);

        $screen_paths = $this->PathsGenerate($screen_files_provider);

        $screen_paths->setValid(false);

        $tested_method = $this->app->make(ScreenDBSaver::class);

        //WHEN
        $response = $tested_method->saveScreen($screen_paths);

        //THEN
        $this->assertIsBool($response);
        $this->assertFalse($response);
    }
}
