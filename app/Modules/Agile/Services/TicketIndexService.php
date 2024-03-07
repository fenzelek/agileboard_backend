<?php

namespace App\Modules\Agile\Services;

use App\Models\Db\Project;
use App\Models\Other\RoleType;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class TicketIndexService
{
    /**
     * @param Project $project
     * @param $tickets
     *
     * @return mixed
     */
    public function getTicketsViaPermissions(Project $project, $tickets)
    {
        $user = Auth::user();
        $roles = ['reporter', 'assigned', 'not_assigned'];
        $permissions = $project->permission->getTicketShowPermissions($user);
        $permissions_intersect = array_intersect($roles, $permissions);

        if (empty($permissions)) {
            return $tickets->whereRaw('0 = 1');
        }

        if (in_array('all', $permissions)) {
            return $tickets;
        }

        if (! empty($permissions_intersect)) {
            $tickets->where(function ($query) use ($user, $permissions) {
                if (in_array('reporter', $permissions)) {
                    $query->orWhere('reporter_id', $user->id);
                }

                if (in_array('assigned', $permissions)) {
                    $query->orWhere('assigned_id', $user->id);
                }

                if (in_array('not_assigned', $permissions)) {
                    $query->orWhereNull('assigned_id');
                }
            });
        }

        return $tickets;
    }

    /**
     * @param Request $request
     * @param $tickets
     * @param Project $project
     *
     * @return Builder
     */
    public function settingsTickets(Request $request, $tickets, Project $project)
    {
        $tickets = $this->getTicketsViaPermissions($project, $tickets);
        $tickets = $tickets->with('stories', 'assignedUser', 'sprint');

        if ($request->input('sprint_id') !== null) {
            $tickets = $tickets->where('sprint_id', $request->input('sprint_id'));
        }

        if ($request->has('hidden')) {
            $tickets = $tickets->where('hidden', $request->input('hidden'));
        }

        if ($request->input('story_id')) {
            $tickets = $tickets->whereHas('stories', function ($q) use ($request) {
                $q->where('stories.id', $request->input('story_id'));
            });
        }

        if ($request->input('story_ids')) {
            $tickets = $tickets->whereHas('stories', function ($q) use ($request) {
                $q->whereIn('stories.id', $request->input('story_ids'));
            });
        }

        if ($request->has('search') && $request->input('search', '') != '') {
            $tickets = $tickets->where(function ($q) use ($request) {
                $q->where('title', 'LIKE', '%' . $request->input('search') . '%');
                $q->orWhere('name', 'LIKE', '%' . $request->input('search') . '%');
            });
        }

        $tickets = $tickets->orderBy(
            $request->input('sort_by', 'priority'),
            $request->input('sort_type', 'ASC')
        );

        $tickets->with('timeTrackingGeneralSummary', 'project');

        return $tickets;
    }

    public function checkActivityPermission(Collection $tickets): Collection
    {
        $access = collect(auth()->user()->getRoles())
            ->intersect([RoleType::OWNER, RoleType::ADMIN, RoleType::DEVELOPER])
            ->isNotEmpty();

        $tickets->map(function ($ticket) use ($access) {
            $ticket->activity_permission = $access;
        });

        return $tickets;
    }
}
