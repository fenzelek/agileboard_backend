<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectUser extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $data = parent::toArray($request);

        $data['user'] = ['data' => $this->user];
        $data['role'] = ['data' => $this->role];

        if ($request->input('user_id')) {
            $project_permission = $this->project->permission;
            $data['project_permission'] = [
                'ticket_show' => $project_permission->getTicketShowPermissions(
                    $this->user,
                    false
                ),
                'ticket_create' => $project_permission->ticket_create,
                'ticket_update' => $project_permission->ticket_update,
                'ticket_destroy' => $project_permission->ticket_destroy,
                'ticket_comment_create' => $project_permission->ticket_comment_create,
                'ticket_comment_update' => $project_permission->ticket_comment_update,
                'ticket_comment_destroy' => $project_permission->ticket_comment_destroy,
            ];
        }

        return $data;
    }
}
