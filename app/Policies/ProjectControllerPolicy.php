<?php

namespace App\Policies;

use App\Models\Db\Project;
use App\Models\Db\User;

class ProjectControllerPolicy extends BasePolicy
{
    protected $group = 'project';

    public function show(User $user, Project $project)
    {
        return $this->hasAccessToProject($user, $project);
    }

    public function update(User $user, Project $project)
    {
        return $this->hasAccessToProject($user, $project);
    }

    public function close(User $user, Project $project)
    {
        return $this->hasAccessToProject($user, $project);
    }

    public function destroy(User $user, Project $project)
    {
        return $this->hasAccessToProject($user, $project);
    }

    public function basicInfo(User $user, Project $project)
    {
        if ($user->isAssignedToProject($project) && $user->isApprovedInCompany($project->company)) {
            return true;
        }

        return false;
    }
}
