<?php

namespace Tests\Unit\App\Modules\TimeTracker\Services\PathGenerator;

use App\Models\Db\Package;
use App\Models\Other\RoleType;
use App\Modules\TimeTracker\Http\Requests\AddScreenshots;
use Illuminate\Http\UploadedFile;

trait PathGeneratorTrait
{
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
}
