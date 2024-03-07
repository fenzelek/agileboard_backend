<?php

namespace App\Modules\Project\Observers;

use App\Models\Db\Project;
use App\Models\Db\ProjectPermission;

class ProjectObserver
{
    /**
     * Listen to the Project created event.
     *
     * @param Project $project
     *
     * @return void
     */
    public function created(Project $project)
    {
        $project->permission()->create(ProjectPermission::DEFAULT_PERMISSIONS);
    }
}
