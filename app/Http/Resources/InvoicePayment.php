<?php

namespace App\Http\Resources;

class InvoicePayment extends AbstractResource
{
    /**
     * @inheritdoc
     */
    protected $fields = [
        'id',
        'invoice_id',
        'amount',
        'payment_method_id',
        'special_partial_payment',
        'registrar_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function toArray($request)
    {
        $data = parent::toArray($request);

        $data['created_at'] = $this->created_at ? $this->created_at->toDateTimeString() : null;
        $data['updated_at'] = $this->updated_at ? $this->updated_at->toDateTimeString() : null;
        $data['deleted_at'] = $this->deleted_at ? $this->deleted_at->toDateTimeString() : null;
        $data['amount'] = $this->undoNormalizeAmount();

        return $data;
    }
}
