<?php

namespace App\Policies;

use App\Models\Db\Project;
use App\Models\Db\Ticket;
use App\Models\Db\User;

class TicketControllerPolicy extends BasePolicy
{
    protected $group = 'ticket';

    public function index(User $user, Project $project)
    {
        return $this->hasAccessToProjectOrIsAdminOrOwnerCompany($user, $project);
    }

    public function changePriority(User $user, Project $project, Ticket $ticket)
    {
        return $this->hasAccessToTicket($user, $ticket, $project);
    }

    public function store(User $user, Project $project)
    {
        if (! $project->permission->canCreateTicket($user)) {
            return false;
        }

        return $this->hasAccessToProject($user, $project);
    }

    public function update(User $user, Project $project, Ticket $ticket)
    {
        if (! $project->permission->canUpdateTicket($user, $ticket)) {
            return false;
        }

        return $this->hasAccessToTicket($user, $ticket, $project);
    }

    public function show(User $user, Project $project, Ticket $ticket)
    {
        return $this->hasAccessToTicket($user, $ticket, $project);
    }

    public function setFlagToShow(User $user, Project $project, Ticket $ticket)
    {
        return $this->hasAccessToTicket($user, $ticket, $project);
    }

    public function setFlagToHide(User $user, Project $project, Ticket $ticket)
    {
        return $this->hasAccessToTicket($user, $ticket, $project);
    }

    public function destroy(User $user, Project $project, Ticket $ticket)
    {
        if (! $project->permission->canDestroyTicket($user, $ticket)) {
            return false;
        }

        return $this->hasAccessToTicket($user, $ticket, $project);
    }

    public function history(User $user, Project $project, Ticket $ticket)
    {
        return $this->hasAccessToTicket($user, $ticket, $project);
    }
}
