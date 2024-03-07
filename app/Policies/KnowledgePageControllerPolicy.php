<?php

namespace App\Policies;

use App\Models\Db\Knowledge\KnowledgeDirectory;
use App\Models\Db\Knowledge\KnowledgePage;
use App\Models\Db\Project;
use App\Models\Db\User;

class KnowledgePageControllerPolicy extends BasePolicy
{
    protected $group = 'knowledge-page';

    /**
     * Check if user has access to project and to directory if he is trying to get pages from there.
     *
     * @param User $user
     * @param Project $project
     *
     * @return bool
     */
    public function index(User $user, Project $project)
    {
        // If user wants to get pages from directory check his permission
        $directory_id = request()->input('knowledge_directory_id');
        if ($directory_id &&
            ! $this->hasAccessToDirectory($user, $project, KnowledgeDirectory::find($directory_id))) {
            return false;
        }

        return $this->hasAccessToProject($user, $project);
    }

    /**
     * Check if user can access page.
     *
     * @param User $user
     * @param Project $project
     * @param KnowledgePage $page
     *
     * @return bool
     */
    public function show(User $user, Project $project, KnowledgePage $page)
    {
        return $this->hasAccessToPage($user, $project, $page);
    }

    /**
     * Accessible to admin/owner in company or in project, users in project. If user want to create
     * page in directory should have access to it.
     *
     * @param User $user
     * @param Project $project
     *
     * @return bool
     */
    public function store(User $user, Project $project)
    {
        if ($this->managerInProject($user, $project)) {
            return true;
        }

        // If user want to add page to directory check his permission
        $directory_id = request()->input('knowledge_directory_id');
        if ($directory_id !== null) {
            $directory = KnowledgeDirectory::find($directory_id);

            return $this->hasAccessToDirectory($user, $project, $directory);
        }

        return $this->hasAccessToProject($user, $project);
    }

    /**
     * Accessible to users who have access to the page.
     *
     * @param User $user
     * @param Project $project
     * @param KnowledgePage $page
     *
     * @return bool
     */
    public function destroy(User $user, Project $project, KnowledgePage $page)
    {
        return $this->hasAccessToPage($user, $project, $page);
    }

    /**
     * Accessible to users who have access to the page.
     *
     * @param User $user
     * @param Project $project
     * @param KnowledgePage $page
     *
     * @return bool
     */
    public function update(User $user, Project $project, KnowledgePage $page)
    {
        return $this->hasAccessToPage($user, $project, $page);
    }
}
