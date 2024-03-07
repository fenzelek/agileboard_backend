<?php

namespace App\Modules\User\Events;

use App\Events\Event;
use App\Models\Db\User;
use Illuminate\Queue\SerializesModels;

class UserWasAssignedToCompany extends Event
{
    use SerializesModels;

    /**
     * User that was assigned to company.
     *
     * @var User
     */
    public $user;

    /**
     * Id of company user was assigned to.
     *
     * @var int
     */
    public $companyId;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param int $companyId
     */
    public function __construct(User $user, $companyId)
    {
        $this->user = $user;
        $this->companyId = $companyId;
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
}
