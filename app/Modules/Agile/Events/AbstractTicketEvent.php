<?php

namespace App\Modules\Agile\Events;

use App\Models\Db\User;
use App\Events\AbstractEvent;
use App\Models\Db\Project;
use App\Models\Db\Ticket;

abstract class AbstractTicketEvent extends AbstractEvent
{
    public $current_user;

    /**
     * Create a new event instance.
     *
     * CreateProjectEvent constructor.
     * @param Project $project
     * @param Ticket $ticket
     * @param User $current_user
     */
    public function __construct(Project $project, Ticket $ticket, User $current_user)
    {
        $this->project = $project;
        $this->ticket = $ticket;
        $this->current_user = $current_user;
    }

    /**
     * Generate message.
     *
     * @param array $data
     * @return array
     */
    protected function generateMessage($data = [])
    {
        $params_url_search = ['{project_id}', '{title}'];
        $params_url_replace = [$this->ticket->project_id, $this->ticket->title];

        $data['name'] = $this->ticket->name;
        $data['title'] = $this->ticket->title;
        $data['current_user_first_name'] = $this->current_user->first_name;
        $data['current_user_last_name'] = $this->current_user->last_name;

        return [
            'title' => '[' . $this->ticket->title . '] ' . $this->ticket->name,
            'url_title' => $this->ticket->title,
            'url' => config('app_settings.welcome_absolute_url') . str_replace($params_url_search, $params_url_replace, config('app_settings.ticket_url')),
            'content' => trans('notifications.' . $this->getType(), $data),
        ];
    }
}
