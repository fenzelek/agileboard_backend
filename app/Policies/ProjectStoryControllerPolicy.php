<?php

namespace App\Policies;

use App\Models\Db\Project;
use App\Models\Db\Story;
use App\Models\Db\User;

class ProjectStoryControllerPolicy extends BasePolicy
{
    protected $group = 'project-story';

    public function index(User $user, Project $project)
    {
        return $this->hasAccessToProject($user, $project);
    }

    public function store(User $user, Project $project)
    {
        return $this->hasAccessToProject($user, $project);
    }

    public function update(User $user, Project $project, Story $story)
    {
        return $this->hasAccessToStory($user, $project, $story);
    }

    public function destroy(User $user, Project $project, Story $story)
    {
        return $this->hasAccessToStory($user, $project, $story);
    }

    public function show(User $user, Project $project, Story $story)
    {
        return $this->hasAccessToStory($user, $project, $story);
    }
}
