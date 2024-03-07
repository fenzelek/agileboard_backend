<?php

namespace App\Policies;

use App\Models\Db\Project;
use App\Models\Db\TicketComment;
use App\Models\Db\User;

class TicketCommentControllerPolicy extends BasePolicy
{
    protected $group = 'ticket-comment';

    public function store(User $user, Project $project)
    {
        if (! $project->permission->canCreateTicketComment($user)) {
            return false;
        }

        return $this->hasAccessToProject($user, $project);
    }

    public function update(User $user, Project $project, TicketComment $ticket_comment)
    {
        if (! $project->permission->canUpdateTicketComment($user, $ticket_comment)) {
            return false;
        }

        return $this->hasAccessToTicketComment($user, $ticket_comment, $project);
    }

    public function destroy(User $user, Project $project, TicketComment $ticket_comment)
    {
        if (! $project->permission->canDestroyTicketComment($user, $ticket_comment)) {
            return false;
        }

        return $this->hasAccessToTicketComment($user, $ticket_comment, $project);
    }
}
