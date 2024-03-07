<?php

namespace App\Policies;

use App\Models\Db\Project;
use App\Models\Db\User;

class ProjectUserControllerPolicy extends BasePolicy
{
    protected $group = 'project-user';

    public function index(User $user, Project $project)
    {
        return $this->hasAccessToProject($user, $project);
    }

    public function store(User $user, Project $project)
    {
        return $this->hasAccessToProject($user, $project);
    }

    public function destroy(User $user, Project $project)
    {
        return $this->hasAccessToProject($user, $project);
    }
}
