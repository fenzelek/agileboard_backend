<?php

namespace App\Modules\Project\Events;

use App\Events\AbstractEvent;
use App\Helpers\EventTypes;
use App\Models\Db\Project;
use App\Models\Db\Role;
use App\Models\Db\User;
use App\Models\Other\RoleType;

class AssignedEvent extends AbstractEvent
{
    public $user;

    public function __construct(Project $project, User $user)
    {
        $this->project = $project;
        $this->user = $user;
    }

    public function getMessage(): array
    {
        $data['name'] = $this->project->name;
        $data['first_name'] = $this->user->first_name;
        $data['last_name'] = $this->user->last_name;

        return [
            'title' => $this->project->name,
            'url_title' => $this->project->name,
            'url' => config('app_settings.welcome_absolute_url') . str_replace('{project_id}', $this->project->id, config('app_settings.board_url')),
            'content' => trans('notifications.' . $this->getType(), $data),
        ];
    }

    public function getRecipients()
    {
        $return = $this->project->users()
            ->whereIn('project_user.role_id', [
                Role::findByName(RoleType::ADMIN)->id,
                Role::findByName(RoleType::OWNER)->id,
            ])
            ->get();
        $return = $this->addRecipient($return, $this->user, false);

        return $return;
    }

    public function getType(): string
    {
        return EventTypes::PROJECT_ASSIGNED;
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
