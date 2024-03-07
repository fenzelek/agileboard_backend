<?php

namespace App\Modules\Agile\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Db\Project;
use App\Models\Db\TicketComment;
use App\Modules\Agile\Events\CreateCommentEvent;
use App\Modules\Agile\Events\DeleteCommentEvent;
use App\Modules\Agile\Events\UpdateCommentEvent;
use App\Modules\Agile\Http\Requests\TicketCommentStore;
use App\Modules\Agile\Http\Requests\TicketCommentUpdate;
use App\Modules\Agile\Services\TicketInteractionFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TicketCommentController extends Controller
{
    /**
     * Create ticket comment.
     *
     * @param TicketCommentStore $request
     * @param Project $project
     * @return JsonResponse
     */
    public function store(
        TicketCommentStore $request,
        Project $project,
        TicketInteractionFactory $ticket_interaction_factory
    ): JsonResponse {
        $user_id = Auth::id();

        return DB::transaction(function () use (
            $request,
            $project,
            $user_id,
            $ticket_interaction_factory
        ) {
            $new_comment = TicketComment::create([
                'text' => trim($request->input('text')),
                'ticket_id' => $request->input('ticket_id'),
                'user_id' => $user_id,
            ]);

            $ticket_interaction_factory->forNewComment(
                $request,
                $new_comment,
                $project->id,
                $user_id,
            );

            event(new CreateCommentEvent($project, $new_comment->ticket, $new_comment));

            return ApiResponse::responseOk($new_comment, 201);
        });
    }

    /**
     * Update ticket comment.
     *
     * @param TicketCommentUpdate $request
     * @param Project $project
     * @param TicketComment $ticket_comment
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(
        TicketCommentUpdate $request,
        Project $project,
        TicketComment $ticket_comment,
        TicketInteractionFactory $ticket_interaction_factory
    ): JsonResponse {
        $user_id = Auth::id();

        return DB::transaction(function () use (
            $request,
            $project,
            $ticket_comment,
            $user_id,
            $ticket_interaction_factory
        ) {
            $ticket_comment->update(['text' => trim($request->input('text'))]);

            $ticket_interaction_factory->forCommentEdit(
                $request,
                $ticket_comment,
                $project->id,
                $user_id,
            );

            event(new UpdateCommentEvent($project, $ticket_comment->ticket, $ticket_comment));

            return ApiResponse::responseOk($ticket_comment, 200);
        });
    }

    /**
     * Delete comment.
     *
     * @param Project $project
     * @param TicketComment $ticket_comment
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Exception
     */
    public function destroy(Project $project, TicketComment $ticket_comment): JsonResponse
    {
        return DB::transaction(function () use ($project, $ticket_comment) {
            $ticket_comment->interactions()->delete();
            $ticket_comment->delete();

            event(new DeleteCommentEvent($project, $ticket_comment->ticket));

            return ApiResponse::responseOk([], 204);
        });
    }
}
