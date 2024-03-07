<?php

namespace App\Policies;

use App\Models\Db\Project;
use App\Models\Db\User;

class StatusControllerPolicy extends BasePolicy
{
    protected $group = 'status';

    public function index(User $user, Project $project)
    {
        return $this->hasAccessToProject($user, $project);
    }

    public function store(User $user, Project $project)
    {
        return $this->hasAccessToProject($user, $project);
    }

    public function update(User $user, Project $project)
    {
        return $this->hasAccessToProject($user, $project);
    }
}
