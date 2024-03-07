<?php

namespace App\Policies;

use App\Models\Db\Project;
use App\Models\Db\User;

class ProjectPermissionControllerPolicy extends BasePolicy
{
    protected $group = 'project-permissions';

    public function show(User $user, Project $project)
    {
        return $this->hasAccessToProject($user, $project);
    }

    public function update(User $user, Project $project)
    {
        return $this->hasAccessToProject($user, $project);
    }
}
