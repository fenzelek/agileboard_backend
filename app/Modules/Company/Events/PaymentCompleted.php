<?php

namespace App\Modules\Company\Events;

use App\Events\Event;
use App\Models\Db\Payment;
use App\Models\Db\User;
use Illuminate\Queue\SerializesModels;

class PaymentCompleted extends Event
{
    use SerializesModels;

    /**
     * Company owner.
     *
     * @var User
     */
    public $user;

    /**
     * Activation url.
     *
     * @var string|null
     */
    public $payment;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param Payment $payment
     */
    public function __construct(User $user, Payment $payment)
    {
        $this->user = $user;
        $this->payment = $payment;
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
