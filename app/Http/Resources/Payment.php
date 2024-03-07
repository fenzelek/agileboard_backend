<?php

namespace App\Http\Resources;

class Payment extends AbstractResource
{
    /**
     * @inheritdoc
     */
    protected $fields = [
        'id',
        'transaction_id',
        'subscription_id',
        'price_total',
        'currency',
        'vat',
        'external_order_id',
        'status',
        'type',
        'days',
        'expiration_date',
        'created_at',
        'updated_at',
    ];

    /**
     * @inheritdoc
     */
    public function toArray($request)
    {
        $data = parent::toArray($request);
        if ($this->created_at) {
            $data['created_at'] = $this->created_at->toDateTimeString();
        }
        if ($this->updated_at) {
            $data['updated_at'] = $this->updated_at->toDateTimeString();
        }

        return $data;
    }
}
