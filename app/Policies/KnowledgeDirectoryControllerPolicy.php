<?php

namespace App\Policies;

use App\Models\Db\Knowledge\KnowledgeDirectory;
use App\Models\Db\Project;
use App\Models\Db\User;

class KnowledgeDirectoryControllerPolicy extends BasePolicy
{
    protected $group = 'knowledge-directory';

    /**
     * Check if user has access to the project.
     *
     * @param User $user
     * @param Project $project
     *
     * @return bool
     */
    public function index(User $user, Project $project)
    {
        return $this->hasAccessToProject($user, $project);
    }

    /**
     * Check if user has access to the project.
     *
     * @param User $user
     * @param Project $project
     *
     * @return bool
     */
    public function store(User $user, Project $project)
    {
        return $this->hasAccessToProject($user, $project);
    }

    /**
     * Check if user has access to the directory.
     *
     * @param User $user
     * @param Project $project
     *
     * @return bool
     */
    public function update(User $user, Project $project, KnowledgeDirectory $directory)
    {
        return $this->hasAccessToDirectory($user, $project, $directory);
    }

    /**
     * Check if user has access to directory he wants to delete and to directory for which he want
     * to move pages from deleted one.
     *
     * @param User $user
     * @param Project $project
     * @param KnowledgeDirectory $directory
     *
     * @return bool
     */
    public function destroy(User $user, Project $project, KnowledgeDirectory $directory)
    {
        // If user want to move pages to another directory he has to has permission
        $target_id = request()->input('knowledge_directory_id');
        if ($target_id &&
            ! $this->hasAccessToDirectory($user, $project, KnowledgeDirectory::find($target_id))) {
            return false;
        }

        return $this->hasAccessToDirectory($user, $project, $directory);
    }
}
