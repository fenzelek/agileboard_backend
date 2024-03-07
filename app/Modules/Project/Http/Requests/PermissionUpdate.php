<?php

namespace App\Modules\Project\Http\Requests;

use App\Http\Requests\Request;

class PermissionUpdate extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'ticket_create' => 'required|array',
            'ticket_create.roles' => 'present|array',
            'ticket_create.roles.*.name' => 'required|string',
            'ticket_create.roles.*.value' => 'required|boolean',

            'ticket_update' => 'required|array',
            'ticket_update.roles' => 'present|array',
            'ticket_update.roles.*.name' => 'required|string',
            'ticket_update.roles.*.value' => 'required|boolean',
            'ticket_update.relations' => 'present|array',
            'ticket_update.relations.*.name' => 'required|string',
            'ticket_update.relations.*.value' => 'required|boolean',

            'ticket_destroy' => 'required|array',
            'ticket_destroy.roles' => 'present|array',
            'ticket_destroy.roles.*.name' => 'required|string',
            'ticket_destroy.roles.*.value' => 'required|boolean',
            'ticket_destroy.relations' => 'present|array',
            'ticket_destroy.relations.*.name' => 'required|string',
            'ticket_destroy.relations.*.value' => 'required|boolean',

            'ticket_comment_create' => 'required|array',
            'ticket_comment_create.roles' => 'present|array',
            'ticket_comment_create.roles.*.name' => 'required|string',
            'ticket_comment_create.roles.*.value' => 'required|boolean',

            'ticket_comment_update' => 'required|array',
            'ticket_comment_update.roles' => 'present|array',
            'ticket_comment_update.roles.*.name' => 'required|string',
            'ticket_comment_update.roles.*.value' => 'required|boolean',
            'ticket_comment_update.relations' => 'present|array',
            'ticket_comment_update.relations.*.name' => 'required|string',
            'ticket_comment_update.relations.*.value' => 'required|boolean',

            'ticket_comment_destroy' => 'required|array',
            'ticket_comment_destroy.roles' => 'present|array',
            'ticket_comment_destroy.roles.*.name' => 'required|string',
            'ticket_comment_destroy.roles.*.value' => 'required|boolean',
            'ticket_comment_destroy.relations' => 'present|array',
            'ticket_comment_destroy.relations.*.name' => 'required|string',
            'ticket_comment_destroy.relations.*.value' => 'required|boolean',

            'owner_ticket_show' => 'present|array',
            'owner_ticket_show.*.name' => 'required|string',
            'owner_ticket_show.*.value' => 'required|boolean',

            'admin_ticket_show' => 'present|array',
            'admin_ticket_show.*.name' => 'required|string',
            'admin_ticket_show.*.value' => 'required|boolean',

            'developer_ticket_show' => 'present|array',
            'developer_ticket_show.*.name' => 'required|string',
            'developer_ticket_show.*.value' => 'required|boolean',

            'client_ticket_show' => 'present|array',
            'client_ticket_show.*.name' => 'required|string',
            'client_ticket_show.*.value' => 'required|boolean',
        ];
    }
}
