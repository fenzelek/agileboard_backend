<?php

namespace App\Modules\CashFlow\Events;

use App\Events\Event;
use App\Http\Requests\Request;
use App\Models\Db\Receipt;
use Illuminate\Queue\SerializesModels;

class ReceiptWasCreated extends Event
{
    use SerializesModels;

    /**
     * Receipt that was created.
     *
     * @var Receipt
     */
    public $receipt;

    /**
     * Incoming Request.
     *
     * @var Request
     */
    public $request;

    /**
     * Create a new event instance.
     *
     * @param Receipt $receipt
     * @param Request $request
     */
    public function __construct(Receipt $receipt, Request $request)
    {
        $this->receipt = $receipt;
        $this->request = $request;
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
