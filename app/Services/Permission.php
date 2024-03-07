<?php

namespace App\Services;

use App\Interfaces\PermissibleRelationsInterface;
use App\Models\Db\File;
use App\Models\Db\Knowledge\KnowledgeDirectory;
use App\Models\Db\Knowledge\KnowledgePage;
use App\Models\Db\Project;
use App\Models\Db\Sprint;
use App\Models\Db\Ticket;
use App\Models\Db\Story;
use App\Models\Db\TicketComment;
use App\Models\Db\User;

class Permission
{
    /**
     * @var KnowledgeDirectory
     */
    protected $knowledge_directory;

    /**
     * Permission constructor.
     * @param KnowledgeDirectory $knowledge_directory
     */
    public function __construct(KnowledgeDirectory $knowledge_directory)
    {
        $this->knowledge_directory = $knowledge_directory;
    }

    /**
     * Check if user can access project. It using company that were chosen at the moment chosen by
     * user.
     *
     * @param User $user
     * @param Project $project
     *
     * @return bool
     */
    public function hasAccessToProject(User $user, Project $project)
    {
        // Check if project is in selected company
        if ($project->company_id != $user->getSelectedCompanyId()) {
            return false;
        }

        if ($user->isOwnerOrAdmin()) {
            return true;
        }

        // Return true if user is assigned to project
        return (bool) $user->projects()->find($project->id);
    }

    /**
     * Check if user can access project or is admin company or owner company.
     *
     * @param User $user
     * @param Project $project
     *
     * @return bool
     */
    public function hasAccessToProjectOrIsAdminOrOwnerCompany(User $user, Project $project)
    {
        // Check if project is in selected company
        if ($project->company_id != $user->getSelectedCompanyId()) {
            return false;
        }

        if ($user->isOwnerOrAdminInCurrentCompany()) {
            return true;
        }

        // Return true if user is assigned to project
        return (bool) $user->projects()->find($project->id);
    }

    /**
     * Verify if user can manage the project.
     *
     * @param User $user
     * @param Project $project
     *
     * @return bool
     */
    public function isManagerInProject(User $user, Project $project)
    {
        // Check if project is in selected company
        if ($project->company_id != $user->getSelectedCompanyId()) {
            return false;
        }

        if ($user->isOwnerOrAdmin() || $user->managerInProject($project)) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can access project and file is assigned to project and user has permission to
     * file.
     *
     * @param User $user
     * @param File $file
     * @param Project $project
     *
     * @return bool
     */
    public function hasAccessToFile(User $user, File $file, Project $project)
    {
        // check access to file by roles or user id
        return $this->hasAccessToProjectResource($user, $project, $file);
    }

    /**
     * Check if user can access directory.
     *
     * @param User $user
     * @param Project $project
     * @param KnowledgeDirectory $directory
     *
     * @return bool
     */
    public function hasAccessToDirectory(User $user, Project $project, KnowledgeDirectory $directory)
    {
        return $this->hasAccessToProjectResource($user, $project, $directory);
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
    public function hasAccessToPage(User $user, Project $project, KnowledgePage $page)
    {
        return $this->hasAccessToProjectResource($user, $project, $page);
    }

    /**
     * Verify whether user has access to given sprint.
     *
     * @param User $user
     * @param Sprint $sprint
     * @param Project $project
     *
     * @return bool
     */
    public function hasAccessToSprint(User $user, Sprint $sprint, Project $project)
    {
        if ($sprint->project_id != $project->id) {
            return false;
        }

        return $this->hasAccessToProject($user, $project);
    }

    /**
     * Verify whether user has access to given ticket.
     *
     * @param User $user
     * @param TicketComment $ticket_comment
     * @param Project $project
     *
     * @return bool
     */
    public function hasAccessToTicketComment(User $user, TicketComment $ticket_comment, Project $project)
    {
        return $this->hasAccessToTicket($user, $ticket_comment->ticket, $project);
    }

    /**
     * Verify whether user has access to ticket comment.
     *
     * @param User $user
     * @param Ticket $ticket
     * @param Project $project
     *
     * @return bool
     */
    public function hasAccessToTicket(User $user, Ticket $ticket, Project $project)
    {
        if ($ticket->project_id != $project->id) {
            return false;
        }

        return $this->hasAccessToProject($user, $project);
    }

    /**
     * Verify whether user has access to story.
     *
     * @param User $user
     * @param Project $project
     * @param Story $story
     *
     * @return bool
     */
    public function hasAccessToStory(User $user, Project $project, Story $story)
    {
        if ($story->project_id != $project->id) {
            return false;
        }

        return $this->hasAccessToProject($user, $project);
    }

    /**
     * It checks users permissions to access given resource.
     *
     * @param User $user
     * @param Project $project
     * @param PermissibleRelationsInterface $item
     *
     * @return bool
     */
    private function hasAccessToProjectResource(
        User $user,
        Project $project,
        PermissibleRelationsInterface $item
    ) {
        // Check if resource is in project
        if ($item->project_id != $project->id) {
            return false;
        }

        // Check if user has access to project
        if (! $this->hasAccessToProject($user, $project)) {
            return false;
        }

        // Admin or owner has access
        if ($user->isOwnerOrAdmin()) {
            return true;
        }

        $roles = $item->roles()->pluck('role_id');
        $users = $item->users()->pluck('user_id');

        // if the rules are empty, then user has permission to access resource.
        if ($roles->count() == 0 && $users->count() == 0) {
            return true;
        }

        // if user's role or id is assigned to resource, user will have permission to access it
        if ($users->contains($user->id) ||
            $roles->contains($user->getRoleInProject($project))
        ) {
            return true;
        }

        return false;
    }
}
