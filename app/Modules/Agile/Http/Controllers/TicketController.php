<?php

namespace App\Modules\Agile\Http\Controllers;

use App\Http\Resources\InvolvedTransformer;
use App\Models\Db\Involved;
use App\Modules\Agile\Events\ChangePriorityTicketEvent;
use App\Modules\Agile\Events\CreateTicketEvent;
use App\Modules\Agile\Events\DeleteTicketEvent;
use App\Helpers\ApiResponse;
use App\Helpers\ErrorCode;
use App\Http\Controllers\Controller;
use App\Http\Resources\Ticket as TicketTransformer;
use App\Http\Resources\TicketWithDetails;
use App\Http\Resources\TimeTracking\ActivitySummary;
use App\Http\Resources\TimeTracking\User as TimeTrackingUserTransformer;
use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\Integration\TimeTracking\User;
use App\Models\Db\Project;
use App\Models\Db\Status;
use App\Models\Db\Ticket;
use App\Modules\Agile\Events\SetFlagToHideTicketEvent;
use App\Modules\Agile\Events\SetFlagToShowTicketEvent;
use App\Modules\Agile\Events\UpdateTicketEvent;
use App\Modules\Agile\Http\Requests\TicketChangePriority;
use App\Modules\Agile\Http\Requests\TicketIndex;
use App\Modules\Agile\Http\Requests\TicketStore;
use App\Modules\Agile\Services\HistoryService;
use App\Modules\Agile\Services\TicketChangePriorityService;
use App\Modules\Agile\Services\TicketIndexService;
use App\Modules\Agile\Services\TicketInteractionFactory;
use App\Modules\Involved\Services\InvolvedService;
use App\Services\Paginator;
use Auth;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    /**
     * Display list of tickets.
     *
     * @param TicketIndex $request
     * @param Project $project
     * @param TicketIndexService $service
     * @return JsonResponse
     */
    public function index(TicketIndex $request, Project $project, TicketIndexService $service)
    {
        $tickets = $project->tickets();
        $tickets = $service->settingsTickets($request, $tickets, $project);

        if ($limit = $request->input('limit')) {
            $tickets->limit($limit);
        }

        $tickets = $tickets->get();
        $tickets = $service->checkActivityPermission($tickets);

        return ApiResponse::transResponseOk($tickets, 200, [
            Ticket::class => TicketWithDetails::class,
        ]);
    }

    /**
     * Change priority ticket.
     *
     * @param TicketChangePriority $request
     * @param Project $project
     * @param Ticket $ticket
     * @param TicketChangePriorityService $service
     *
     * @return JsonResponse
     */
    public function changePriority(
        TicketChangePriority $request,
        Project $project,
        Ticket $ticket,
        TicketChangePriorityService $service
    ) {
        $sprint_old_id = $ticket->sprint_id;
        $sprint_new_id = $request->input('sprint_id');

        $ticket = $service->run($request, $project, $ticket);

        event(new ChangePriorityTicketEvent($project, $ticket, $sprint_old_id, $sprint_new_id));

        return ApiResponse::responseOk($ticket, 200);
    }

    /**
     * Create new ticket.
     *
     * @param TicketStore $request
     * @param Project $project
     * @param TicketInteractionFactory $ticket_interaction_factory
     * @return JsonResponse
     */
    public function store(
        TicketStore $request,
        Project $project,
        TicketInteractionFactory $ticket_interaction_factory,
        InvolvedService $involved_service
    ): JsonResponse {
        $first_project_status = Status::firstStatus($project->id);

        if (! $first_project_status) {
            return ApiResponse::responseError(
                ErrorCode::PROJECT_NO_STATUSES,
                Response::HTTP_CONFLICT
            );
        }

        $current_priority = Ticket::where('project_id', $project->id)->max('priority');

        return DB::transaction(function () use (
            $project,
            $request,
            $current_priority,
            $first_project_status,
            $ticket_interaction_factory,
            $involved_service
        ) {
            $project->created_tickets = $project->created_tickets + 1;
            $project->save();

            /** @var Ticket $new_ticket */
            $new_ticket = $project->tickets()->create([
                'name' => trim($request->input('name')),
                'sprint_id' => $request->input('sprint_id', 0),
                'status_id' => $first_project_status->id,
                'title' => $project->short_name . '-' . $project->created_tickets,
                'type_id' => $request->input('type_id'),
                'assigned_id' => $request->input('assigned_id'),
                'reporter_id' => $request->input('reporter_id'),
                'description' => trim($request->input('description', '')),
                'estimate_time' => $request->input('estimate_time'),
                'scheduled_time_start' => $request->input('scheduled_time_start'),
                'scheduled_time_end' => $request->input('scheduled_time_end'),
                'priority' => $current_priority ? $current_priority + 1 : 1,
                'hidden' => 0,
            ]);

            $new_ticket->stories()->attach($request->input('story_id'));
            $new_ticket->parentTickets()->attach($request->input('parent_ticket_ids'));
            $new_ticket->subTickets()->attach($request->input('sub_ticket_ids'));

            $ticket_interaction_factory->forNewTicket($request, $new_ticket, $project->id, Auth::user()->id);

            $new_involved_ids = $involved_service->getNewInvolvedIds($request, $new_ticket);
            $involved_service->syncInvolved($request, $new_ticket);
            $ticket_interaction_factory->forInvolvedAssigned($new_involved_ids, $new_ticket, $request->getSelectedCompanyId(), $project->id, Auth::user()->id);

            event(new CreateTicketEvent($project, Ticket::findOrFail($new_ticket->id), Auth::user()));

            $new_ticket->loadMissing('parentTickets');
            $new_ticket->loadMissing('subTickets');

            return ApiResponse::responseOk($new_ticket, 201);
        });
    }

    /**
     * Update ticket.
     *
     * @param TicketStore $request
     * @param Project $project
     * @param Ticket $ticket
     * @param TicketInteractionFactory $ticket_interaction_factory
     * @return mixed
     */
    public function update(
        TicketStore $request,
        Project $project,
        Ticket $ticket,
        TicketInteractionFactory $ticket_interaction_factory,
        InvolvedService $involved_service
    ): JsonResponse {
        return DB::transaction(function () use (
            $project,
            $ticket,
            $request,
            $ticket_interaction_factory,
            $involved_service
        )
        {
            $sprint_old_id = $ticket->sprint_id;
            $sprint_new_id = $request->input('sprint_id');

            $ticket->update([
                'name' => trim($request->input('name')),
                'sprint_id' => $request->input('sprint_id', 0),
                'type_id' => $request->input('type_id'),
                'assigned_id' => $request->input('assigned_id'),
                'reporter_id' => $request->input('reporter_id'),
                'description' => trim($request->input('description', '')),
                'estimate_time' => $request->input('estimate_time'),
                'scheduled_time_start' => $request->input('scheduled_time_start'),
                'scheduled_time_end' => $request->input('scheduled_time_end'),
            ]);

            HistoryService::sync(
                $ticket->id,
                $ticket->id,
                HistoryService::TICKET,
                'story_id',
                $ticket->stories()->pluck('id')->all(),
                (array) $request->input('story_id')
            );

            $ticket->stories()->sync((array) $request->input('story_id'));
            $ticket->parentTickets()->sync($request->input('parent_ticket_ids'));
            $ticket->subTickets()->sync($request->input('sub_ticket_ids'));

            $ticket_interaction_factory->forTicketEdit($request, $ticket, $project->id, Auth::user()->id);

            $new_involved_ids = $involved_service->getNewInvolvedIds($request, $ticket);
            $involved_service->syncInvolved($request, $ticket);
            $ticket_interaction_factory->forInvolvedAssigned($new_involved_ids, $ticket, $request->getSelectedCompanyId(), $project->id, Auth::user()->id);

            event(new UpdateTicketEvent($project, $ticket, $sprint_old_id, $sprint_new_id));

            return ApiResponse::responseOk($ticket, 200);
        });
    }

    /**
     * Show ticket.
     *
     * @param Project $project
     * @param Ticket $ticket
     * @param Guard $auth
     *
     * @return JsonResponse
     */
    public function show(Project $project, Ticket $ticket, Guard $auth)
    {
        $relationships = [
            'files',
            'files.owner',
            'stories',
            'sprint',
            'type',
            'status',
            'comments.user',
            'assignedUser',
            'reportingUser',
            'project',
            'parentTickets.assignedUser',
            'subTickets.assignedUser',
            'involved.user'
        ];

        if (! $auth->user()->clientInProject($project)) {
            array_push($relationships, 'timeTrackingSummary');
        }

        $ticket->load($relationships);
        $ticket['parent_ticket_ids'] = $ticket->parentTickets->pluck('id')->toArray();
        $ticket['sub_ticket_ids'] = $ticket->subTickets->pluck('id')->toArray();

        return ApiResponse::transResponseOk($ticket, 200, [
            User::class => TimeTrackingUserTransformer::class,
            Activity::class => ActivitySummary::class,
            Ticket::class => TicketTransformer::class,
            Involved::class => InvolvedTransformer::class,
        ]);
    }

    /**
     * Set hidden field to false.
     *
     * @param Project $project
     * @param Ticket $ticket
     *
     * @return JsonResponse
     */
    public function setFlagToShow(Project $project, Ticket $ticket)
    {
        return DB::transaction(function () use ($project, $ticket) {
            $ticket->update(['hidden' => false]);

            event(new SetFlagToShowTicketEvent($project, $ticket, $ticket->sprint));

            return ApiResponse::responseOk($ticket, 200);
        });
    }

    /**
     * Set hidden field to true.
     *
     * @param Project $project
     * @param Ticket $ticket
     *
     * @return JsonResponse
     */
    public function setFlagToHide(Project $project, Ticket $ticket)
    {
        return DB::transaction(function () use ($project, $ticket) {
            $ticket->update(['hidden' => true]);

            event(new SetFlagToHideTicketEvent($project, $ticket, $ticket->sprint));

            return ApiResponse::responseOk($ticket, 200);
        });
    }

    /**
     * Soft deleted story.
     *
     * @param Project $project
     * @param Ticket $ticket
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function destroy(
        Project $project,
        Ticket $ticket,
        Guard $auth,
        InvolvedService $involved_service,
        TicketInteractionFactory $ticket_interaction_factory): JsonResponse
    {
        $user = $auth->user();

        return DB::transaction(function () use ($project, $ticket, $involved_service, $ticket_interaction_factory, $user) {
            $involved_users = $involved_service->getInvolvedUsers($ticket);

            $ticket->interactions()->delete();
            $involved_service->deleteInvolved($ticket);
            $ticket->delete();

            $ticket_interaction_factory->forInvolvedDeleted(
                $involved_users->pluck('user_id'),
                $ticket,
                $project,
                $user->id);

            event(new DeleteTicketEvent($project, $ticket, Auth::user()));

            return ApiResponse::responseOk([], 204);
        });
    }

    /**
     * History list.
     *
     * @param Project $project
     * @param Ticket $ticket
     * @param Paginator $paginator
     *
     * @return JsonResponse
     */
    public function history(Project $project, Ticket $ticket, Paginator $paginator)
    {
        return ApiResponse::responseOk(
            $paginator->get(
                $ticket->history()->with('user', 'field'),
                'tickets.history',
                ['project' => $project->id, 'ticket' => $ticket->id]
            )
        );
    }
}