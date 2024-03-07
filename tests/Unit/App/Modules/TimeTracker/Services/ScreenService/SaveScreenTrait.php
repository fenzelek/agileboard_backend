<?php

namespace Tests\Unit\App\Modules\TimeTracker\Services\ScreenService;

use App\Models\Db\Package;
use App\Models\Other\RoleType;
use App\Modules\TimeTracker\Http\Requests\AddScreenshots;
use App\Modules\TimeTracker\Services\PathGenerator;
use App\Modules\TimeTracker\Services\StorageScreenshot;
use Illuminate\Http\UploadedFile;

trait SaveScreenTrait
{
    public function getPath($project, $company, $separate_folder): string
    {
        return $this->main_folder . '/' . $project->short_name . '_' . str_slug($company->name) . '/' . $this->user->id . '/' . $separate_folder . '/';
    }

    protected function prepareCompany()
    {
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::CEP_FREE);
        $company->roles()->attach([4]);

        return $company;
    }

    protected function getScreenFilesProvider($project, $name)
    {
        $upload_file = UploadedFile::fake()->image('name.jpeg');
        $entry_data = [
            'screen' => $upload_file,
            'screen_id' => $name,
            'project_id' => $project->id,
        ];

        return new AddScreenshots($entry_data);
    }

    protected function PathsGenerate(AddScreenshots $screen_files_provider)
    {
        $path_generator = $this->app->make(PathGenerator::class);
        $paths = $path_generator->pathBuilder($screen_files_provider);

        $store_image = $this->app->make(StorageScreenshot::class);
        $screen_paths = $store_image->storeImage($paths, $screen_files_provider);

        return $screen_paths;
    }
}
