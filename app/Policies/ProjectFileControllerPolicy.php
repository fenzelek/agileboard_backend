<?php

namespace App\Policies;

use App\Models\Db\File;
use App\Models\Db\Project;
use App\Models\Db\User;

class ProjectFileControllerPolicy extends BasePolicy
{
    protected $group = 'project-file';

    public function index(User $user, Project $project)
    {
        return $this->hasAccessToProject($user, $project);
    }

    public function store(User $user, Project $project)
    {
        return $this->hasAccessToProject($user, $project);
    }

    public function destroy(User $user, Project $project, File $file)
    {
        return $this->hasAccessToFile($user, $file, $project);
    }

    public function update(User $user, Project $project, File $file)
    {
        return $this->hasAccessToFile($user, $file, $project);
    }

    public function show(User $user, Project $project, File $file)
    {
        return $this->hasAccessToFile($user, $file, $project);
    }

    public function download(User $user, Project $project, File $file)
    {
        return $this->hasAccessToFile($user, $file, $project);
    }
}
