<?php

namespace App\Helpers;

class EventTypes
{
    //PROJECT
    const PROJECT_ASSIGNED = 'project-assigned';

    //SPRINT
    const SPRINT_STORE = 'sprint-store';
    const SPRINT_UPDATE = 'sprint-update';
    const SPRINT_ACTIVE = 'sprint-active';
    const SPRINT_PAUSE = 'sprint-pause';
    const SPRINT_RESUME = 'sprint-resume';
    const SPRINT_LOCK = 'sprint-lock';
    const SPRINT_UNLOCK = 'sprint-unlock';
    const SPRINT_CLOSE = 'sprint-close';
    const SPRINT_DELETE = 'sprint-delete';
    const SPRINT_CHANGE_PRIORITY = 'sprint-change-priority';

    // TICKET
    const TICKET_STORE = 'ticket-store';
    const TICKET_DELETE = 'ticket-delete';
    const TICKET_CHANGE_PRIORITY = 'ticket-change-priority';
    const TICKET_UPDATE = 'ticket-update';
    const TICKET_SET_SHOW_FLAG = 'ticket-set-show-flag';
    const TICKET_SET_HIDE_FLAG = 'ticket-set-hide-flag';
    const TICKET_ASSIGNED = 'ticket-assigned';
    const TICKET_MOVE = 'ticket-move';
    const TICKET_EXPIRED_SCHEDULED_DATE = 'ticket-expired-scheduled-date';
    const TICKET_TODAY_SCHEDULED_DATE = 'ticket-today-scheduled-date';

    // TICKET COMMENT
    const TICKET_COMMENT_STORE = 'ticket-comment-store';
    const TICKET_COMMENT_UPDATE = 'ticket-comment-update';
    const TICKET_COMMENT_DELETE = 'ticket-comment-delete';

    //STATUSES
    const STATUSES_STORE = 'statuses-store';
    const STATUSES_UPDATE = 'statuses-update';
}
