<?php

return [

    \App\Helpers\EventTypes::PROJECT_ASSIGNED => 'The :first_name :last_name user has been assigned to the :name project.',

    \App\Helpers\EventTypes::TICKET_STORE => 'Ticket [:title] ":name" was created by :current_user_first_name :current_user_last_name.',
    \App\Helpers\EventTypes::TICKET_DELETE => 'Ticket [:title] ":name" was removed by :current_user_first_name :current_user_last_name.',
    \App\Helpers\EventTypes::TICKET_ASSIGNED => 'Ticket [:title] ":name" was assigned to :first_name :last_name by :current_user_first_name :current_user_last_name.',
    \App\Helpers\EventTypes::TICKET_MOVE => 'Ticket [:title] ":name" was moved to column :status_name by :current_user_first_name :current_user_last_name.',
    \App\Helpers\EventTypes::TICKET_EXPIRED_SCHEDULED_DATE => 'In the [:title] ticket ":name", the scheduled completion day has been exceeded.',
    \App\Helpers\EventTypes::TICKET_TODAY_SCHEDULED_DATE => 'Ticket [:title] ":name" today has scheduled day of work completion.',

    \App\Helpers\EventTypes::TICKET_COMMENT_STORE => ':first_name :last_name added comment: :comment',

    'daily_ticket_report' => [
        'subject' => 'Daily Ticket Report',
        'welcome' => 'Hello :name',
        'report_date' => 'Report from :date',
        'th' => [
            'task' => 'Task',
            'user' => 'User',
            'field' => 'Field',
            'prev_value' => 'Prev value',
            'new_value' => 'New value',
            'date' => 'Date',
        ],
    ],
];
