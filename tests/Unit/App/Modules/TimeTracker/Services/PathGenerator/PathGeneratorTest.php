<?php

namespace Tests\Unit\App\Modules\TimeTracker\Services\PathGenerator;

use App\Models\Db\Project;
use App\Modules\TimeTracker\Models\ScreenPaths;
use App\Modules\TimeTracker\Services\PathGenerator;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PathGeneratorTest extends TestCase
{
    use DatabaseTransactions;
    use PathGeneratorTrait;

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
     * @case success, build paths
     *
     * @test
     */
    public function success_generate_paths_for_save()
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

        $tested_method = $this->app->make(PathGenerator::class);

        //WHEN
        $response = $tested_method->pathBuilder($screen_files_provider);

        //THEN
        $this->assertInstanceOf(ScreenPaths::class, $response);
        $this->assertStringContainsString($name, $response->getScreenName());
        $this->assertStringContainsString($name, $response->getStoragePathThumb());
        $this->assertStringContainsString($name, $response->getStoragePathUrl());
        $this->assertStringContainsString($this->user->id, $response->getFilePathThumb());
        $this->assertStringContainsString($this->user->id, $response->getFilePathThumb());
        $this->assertStringContainsString($project->short_name, $response->getFilePathThumb());
        $this->assertStringContainsString($project->short_name, $response->getFilePathUrl());
        $this->assertTrue($response->isValid());
    }
}
