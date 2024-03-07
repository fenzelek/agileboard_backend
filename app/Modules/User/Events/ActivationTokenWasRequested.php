<?php

namespace App\Modules\User\Events;

use App\Events\Event;
use App\Models\Db\User;
use Illuminate\Queue\SerializesModels;

class ActivationTokenWasRequested extends Event
{
    use SerializesModels;

    /**
     * User that was activated.
     *
     * @var User
     */
    public $user;

    /**
     * @var
     */
    public $url;

    /**
     * Language for sending email.
     *
     * @var string|null
     */
    public $language;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param $url
     * @param string $language
     */
    public function __construct(User $user, $url, $language = 'en')
    {
        $this->user = $user;
        $this->url = $url;
        $this->language = $language;
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
