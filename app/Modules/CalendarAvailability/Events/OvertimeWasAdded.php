<?php

namespace App\Modules\CalendarAvailability\Events;

use App\Models\Db\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class OvertimeWasAdded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * User that was created.
     *
     * @var User
     */
    public $process_user;

    /**
     * Create a new event instance.
     *
     * @param User $process_user
     */
    public function __construct(User $process_user)
    {
        $this->process_user = $process_user;
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }

    public function getMailableUsers(): Collection
    {
        $company = $this->process_user->selectedCompany();

        return $company->getAdministration()->get();
    }
}
