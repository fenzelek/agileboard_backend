<?php

namespace App\Http\Resources;

class ProjectWithDetailsForAdmin extends ProjectWithDetails
{
    /**
     * @inheritdoc
     */
    protected $fields = [
        'id',
        'company_id',
        'name',
        'short_name',
        'created_tickets',
        'time_tracking_visible_for_clients',
        'status_for_calendar_id',
        'language',
        'email_notification_enabled',
        'slack_notification_enabled',
        'slack_webhook_url',
        'slack_channel',
        'color',
        'closed_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
