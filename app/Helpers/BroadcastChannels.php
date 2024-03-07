<?php

namespace App\Helpers;

class BroadcastChannels
{
    //SPRINT
    const SPRINT_STORE = 'new-sprint';
    const SPRINT_UPDATE = 'update-sprint';
    const SPRINT_CHANGE_STATUS = 'change-status-sprint';
    const SPRINT_LOCK = 'lock-sprint';
    const SPRINT_DELETE = 'delete-sprint';
    const SPRINT_CHANGE_PRIORITY = 'change-priority-sprint';

    // TICKET
    const TICKET_STORE = 'store-ticket';
    const TICKET_DELETE = 'delete-ticket';
    const TICKET_CHANGE = 'change-ticket';
    const TICKET_CHANGE_PRIORITY = 'change-priority-ticket';
    const TICKET_CHANGE_MIN = 'change-min-ticket';

    // TICKET COMMENT
    const TICKET_COMMENT = 'comment-ticket';

    //STATUSES
    const STATUSES_CHANGE = 'change-statuses';
}
