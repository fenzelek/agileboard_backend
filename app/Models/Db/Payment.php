<?php

namespace App\Models\Db;

use App\Models\Other\PaymentStatus;

class Payment extends Model
{
    const TYPE_SIMPLE = 0;
    const TYPE_CARD = 1;
    protected $guarded = [];

    protected $dates = ['expiration_date'];

    /**
     * RELATIONS.
     */

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * METHODS.
     */
    public function isWaitingForPayment()
    {
        return in_array($this->status, PaymentStatus::WAITING_STATUSES);
    }
}
