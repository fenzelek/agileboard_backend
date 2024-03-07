<?php

namespace App\Modules\Agile\Events;

use App\Events\AbstractEvent;
use App\Helpers\BroadcastChannels;
use App\Models\Db\Project;
use App\Models\Db\Ticket;
use App\Models\Db\TicketComment;

abstract class AbstractCommentEvent extends AbstractEvent
{
    public $comment;

    /**
     * Create a new event instance.
     *
     * CreateProjectEvent constructor.
     * @param Project $project
     * @param Ticket $ticket
     */
    public function __construct(Project $project, Ticket $ticket, TicketComment $comment)
    {
        $this->project = $project;
        $this->ticket = $ticket;
        $this->comment = $comment;
    }

    /**
     * Get recipients.
     *
     * @return mixed
     */
    public function getRecipients()
    {
        $return = $this->addRecipient(collect([]), $this->ticket->assignedUser);
        $return = $this->addRecipient($return, $this->ticket->reportingUser);

        return $this->removeRecipient($return, $this->comment->user_id);
    }

    /**
     * Get broadcast name channel.
     *
     * @return string
     */
    public function getBroadcastChannel(): string
    {
        return BroadcastChannels::TICKET_COMMENT;
    }

    /**
     * Get broadcast data.
     *
     * @return array
     */
    public function getBroadcastData(): array
    {
        return [
            'project_id' => $this->project->id,
            'ticket_id' => $this->ticket->id,
            'sprint_id' => $this->ticket->sprint_id,
        ];
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

        return [
            'title' => '[' . $this->ticket->title . '] ' . $this->ticket->name,
            'url_title' => $this->ticket->title,
            'url' => config('app_settings.welcome_absolute_url') . str_replace($params_url_search, $params_url_replace, config('app_settings.ticket_url')),
            'content' => trans('notifications.' . $this->getType(), $data),
        ];
    }
}
