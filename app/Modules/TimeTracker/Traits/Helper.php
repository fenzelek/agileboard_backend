<?php

namespace App\Modules\TimeTracker\Traits;

use App\Models\Db\Company;
use App\Models\Db\Project;
use App\Modules\TimeTracker\Http\Requests\Contracts\IAddScreens;

trait Helper
{
    /**
     * @param Project $project
     */
    protected function getCompanyName(Project $project): string
    {
        $company = Company::where('id', $project->company_id)->select('name')->first();

        return str_slug($company->name);
    }

    protected function getProject(IAddScreens $screen_files_provider): Project
    {
        return Project::findOrFail($screen_files_provider->getProjectId());
    }
}
