<?php

namespace App\Policies;

use App\Models\Db\Project;
use App\Models\Db\Sprint;
use App\Models\Db\User;

class SprintControllerPolicy extends BasePolicy
{
    protected $group = 'sprint';

    public function index(User $user, Project $project)
    {
        return $this->hasAccessToProject($user, $project);
    }

    public function store(User $user, Project $project)
    {
        return $this->hasAccessToProject($user, $project);
    }

    public function update(User $user, Project $project, Sprint $sprint)
    {
        return $this->hasAccessToSprint($user, $sprint, $project);
    }

    public function activate(User $user, Project $project, Sprint $sprint)
    {
        return $this->hasAccessToSprint($user, $sprint, $project);
    }

    public function pause(User $user, Project $project, Sprint $sprint)
    {
        return $this->hasAccessToSprint($user, $sprint, $project);
    }

    public function resume(User $user, Project $project, Sprint $sprint)
    {
        return $this->hasAccessToSprint($user, $sprint, $project);
    }

    public function lock(User $user, Project $project, Sprint $sprint)
    {
        return $this->hasAccessToSprint($user, $sprint, $project);
    }

    public function unlock(User $user, Project $project, Sprint $sprint)
    {
        return $this->hasAccessToSprint($user, $sprint, $project);
    }

    public function close(User $user, Project $project, Sprint $sprint)
    {
        return $this->hasAccessToSprint($user, $sprint, $project);
    }

    public function changePriority(User $user, Project $project)
    {
        return $this->hasAccessToProject($user, $project);
    }

    public function destroy(User $user, Project $project, Sprint $sprint)
    {
        return $this->hasAccessToSprint($user, $sprint, $project);
    }

    public function export(User $user, Project $project, Sprint $sprint): bool
    {
        return $this->hasAccessToSprint($user, $sprint, $project);
    }
}
