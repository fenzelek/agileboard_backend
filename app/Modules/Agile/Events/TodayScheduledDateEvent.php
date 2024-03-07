<?php

namespace App\Modules\Agile\Events;

use App\Events\AbstractEvent;
use App\Helpers\EventTypes;
use App\Models\Db\Project;
use App\Models\Db\Ticket;

class TodayScheduledDateEvent extends AbstractEvent
{
    public function __construct(Project $project, Ticket $ticket)
    {
        $this->project = $project;
        $this->ticket = $ticket;
    }

    public function getMessage(): array
    {
        $params_url_search = ['{project_id}', '{title}'];
        $params_url_replace = [$this->ticket->project_id, $this->ticket->title];

        $data['name'] = $this->ticket->name;
        $data['title'] = $this->ticket->title;

        return [
            'title' => '[' . $this->ticket->title . '] ' . $this->ticket->name,
            'url_title' => $this->ticket->title,
            'url' => config('app_settings.welcome_absolute_url') . str_replace($params_url_search, $params_url_replace, config('app_settings.ticket_url')),
            'content' => trans('notifications.' . $this->getType(), $data),
        ];
    }

    public function getRecipients()
    {
        return $this->addRecipient(collect([]), $this->ticket->assignedUser);
    }

    public function getType(): string
    {
        return EventTypes::TICKET_TODAY_SCHEDULED_DATE;
    }

    public function getBroadcastChannel(): string
    {
        return '';
    }

    public function getBroadcastData(): array
    {
        return [];
    }
}
