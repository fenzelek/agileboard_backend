<?php

namespace App\Policies;

use App\Models\Db\Project;
use App\Models\Db\User;

class ReportControllerPolicy extends BasePolicy
{
    protected $group = 'report-agile';

    public function daily(User $user)
    {
        $project_id = $this->request->input('project_id');

        if (! $project_id) {
            return true;
        }

        $project = Project::query()->findOrFail($project_id);

        return $this->hasAccessToProjectOrIsAdminOrOwnerCompany($user, $project);
    }
}
