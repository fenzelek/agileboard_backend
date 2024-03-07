<?php

namespace App\Models\Other;

use App\Models\Db\InvoiceItem;

class InvoiceItemPaid
{
    /**
     * @var InvoiceItem
     */
    public $paid;

    /**
     * InvoiceItemPaid constructor.
     *
     * @param InvoiceItem $paid
     */
    public function __construct(InvoiceItem $paid)
    {
        $this->paid = $paid;
    }

    /**
     * Render net and gross quotas.
     *
     * @return array
     */
    public function render()
    {
        return [
            'data' => [
                'gross' => denormalize_price($this->paid->gross),
                'net' => denormalize_price($this->paid->net),
            ],
        ];
    }
}
