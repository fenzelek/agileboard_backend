<?php

namespace App\Modules\Notification\Services;

use App\Helpers\EventTypes;

class EventChannelsSettings
{
    const MAIL = 'mail';
    const SLACK = 'slack';
    const BROADCAST = 'broadcast';

    /**
     * Get selected settings of type.
     *
     * @param $type
     * @return array|mixed
     */
    public function get($type)
    {
        if (isset($this->all()[$type])) {
            return $this->all()[$type];
        }

        return [];
    }

    /**
     * All settings.
     *
     * @return array
     */
    private function all()
    {
        return [
            EventTypes::PROJECT_ASSIGNED => [self::MAIL, self::SLACK],
            EventTypes::SPRINT_STORE => [self::BROADCAST],
            EventTypes::SPRINT_UPDATE => [self::BROADCAST],
            EventTypes::SPRINT_ACTIVE => [self::BROADCAST],
            EventTypes::SPRINT_PAUSE => [self::BROADCAST],
            EventTypes::SPRINT_CLOSE => [self::BROADCAST],
            EventTypes::SPRINT_DELETE => [self::BROADCAST],
            EventTypes::SPRINT_CHANGE_PRIORITY => [self::BROADCAST],
            EventTypes::TICKET_STORE => [self::BROADCAST, self::MAIL, self::SLACK],
            EventTypes::TICKET_DELETE => [self::BROADCAST, self::MAIL, self::SLACK],
            EventTypes::TICKET_CHANGE_PRIORITY => [self::BROADCAST],
            EventTypes::TICKET_UPDATE => [self::BROADCAST],
            EventTypes::TICKET_SET_SHOW_FLAG => [self::BROADCAST],
            EventTypes::TICKET_SET_HIDE_FLAG => [self::BROADCAST],
            EventTypes::TICKET_ASSIGNED => [self::MAIL, self::SLACK],
            EventTypes::TICKET_MOVE => [self::MAIL, self::SLACK],
            EventTypes::TICKET_EXPIRED_SCHEDULED_DATE => [self::MAIL],
            EventTypes::TICKET_TODAY_SCHEDULED_DATE => [self::MAIL],
            EventTypes::TICKET_COMMENT_STORE => [self::BROADCAST, self::MAIL, self::SLACK],
            EventTypes::TICKET_COMMENT_UPDATE => [self::BROADCAST],
            EventTypes::TICKET_COMMENT_DELETE => [self::BROADCAST],
            EventTypes::STATUSES_STORE => [self::BROADCAST],
            EventTypes::STATUSES_UPDATE => [self::BROADCAST],
        ];
    }
}
