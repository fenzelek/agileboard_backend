<?php

namespace App\Policies;

use App\Models\Db\File;
use App\Models\Db\Knowledge\KnowledgeDirectory;
use App\Models\Db\Knowledge\KnowledgePage;
use App\Models\Db\Project;
use App\Models\Db\Sprint;
use App\Models\Db\Ticket;
use App\Models\Db\Story;
use App\Models\Db\TicketComment;
use App\Models\Db\User;
use App\Services\Permission;
use Illuminate\Contracts\Config\Repository as Config;
use Psr\Log\LoggerInterface as Log;
use Illuminate\Http\Request;
use App\Services\Mnabialek\LaravelAuthorize\Contracts\Permissionable;
use App\Services\Mnabialek\LaravelAuthorize\Policies\BasePolicyController;

class BasePolicy extends BasePolicyController
{
    /**
     * Permission service.
     *
     * @var Permission
     */
    protected $permission;

    /**
     * BasePolicy constructor.
     *
     * @param Log $log
     * @param Config $config
     * @param Permissionable $permService
     * @param Request $request
     * @param Permission $permission
     */
    public function __construct(
        Log $log,
        Config $config,
        Permissionable $permService,
        Request $request,
        Permission $permission
    ) {
        parent::__construct($log, $config, $permService, $request);

        $this->permission = $permission;
    }

    /**
     * It verify user access to page.
     *
     * @param User $user
     * @param Project $project
     * @param KnowledgePage|null $page
     *
     * @return bool
     */
    public function hasAccessToPage(User $user, Project $project, KnowledgePage $page)
    {

        // Check if resource is in project
        if ($page->project_id != $project->id) {
            return false;
        }

        if ($this->managerInProject($user, $project)) {
            return true;
        }

        // If page is in directory check user access
        if ($page->directory !== null &&
            ! $this->hasAccessToDirectory($user, $project, $page->directory)
        ) {
            return false;
        }

        return $this->permission->hasAccessToPage($user, $project, $page);
    }

    /**
     * Verify whether user has access to project.
     *
     * @param User $user
     * @param Project $project
     *
     * @return bool
     */
    protected function hasAccessToProject(User $user, Project $project)
    {
        return $this->permission->hasAccessToProject($user, $project);
    }

    /**
     * Verify whether user has access to project or is admin company or owner company.
     *
     * @param User $user
     * @param Project $project
     *
     * @return bool
     */
    protected function hasAccessToProjectOrIsAdminOrOwnerCompany(User $user, Project $project)
    {
        return $this->permission->hasAccessToProjectOrIsAdminOrOwnerCompany($user, $project);
    }

    /**
     * Verify if user is admin/owner in project.
     *
     * @param User $user
     * @param Project $project
     *
     * @return bool
     */
    protected function managerInProject(User $user, Project $project)
    {
        return $this->permission->isManagerInProject($user, $project);
    }

    /**
     * Verify whether user has access to directory.
     *
     * @param User $user
     * @param Project $project
     * @param KnowledgeDirectory $directory
     *
     * @return bool
     */
    protected function hasAccessToDirectory(User $user, Project $project, KnowledgeDirectory $directory)
    {
        if ($this->hasAccessToProject($user, $project)) {
            return $this->permission->hasAccessToDirectory($user, $project, $directory);
        }

        return false;
    }

    /**
     * Verify whether user has access to file.
     *
     * @param User $user
     * @param File $file
     * @param Project $project
     *
     * @return bool
     */
    protected function hasAccessToFile(User $user, File $file, Project $project)
    {
        return $this->permission->hasAccessToFile($user, $file, $project);
    }

    /**
     * Verify whether user has access to sprint.
     *
     * @param User $user
     * @param Sprint $sprint
     * @param Project $project
     *
     * @return bool
     */
    protected function hasAccessToSprint(User $user, Sprint $sprint, Project $project)
    {
        return $this->permission->hasAccessToSprint($user, $sprint, $project);
    }

    /**
     * Verify whether user has access to ticket.
     *
     * @param User $user
     * @param Ticket $ticket
     * @param Project $project
     *
     * @return bool
     */
    protected function hasAccessToTicket(User $user, Ticket $ticket, Project $project)
    {
        return $this->permission->hasAccessToTicket($user, $ticket, $project);
    }

    /**
     * Verify whether user has access to ticket comment.
     *
     * @param User $user
     * @param  TicketComment $ticket_comment
     * @param Project $project
     *
     * @return bool
     */
    protected function hasAccessToTicketComment(User $user, TicketComment $ticket_comment, Project $project)
    {
        return $this->permission->hasAccessToTicketComment($user, $ticket_comment, $project);
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
    protected function hasAccessToStory(User $user, Project $project, Story $story)
    {
        return $this->permission->hasAccessToStory($user, $project, $story);
    }
}
