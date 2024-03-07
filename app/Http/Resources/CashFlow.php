<?php

namespace App\Http\Resources;

class CashFlow extends AbstractResource
{
    /**
     * @inheritdoc
     */
    protected $fields = [
        'id',
        'company_id',
        'user_id',
        'receipt_id',
        'amount',
        'direction',
        'description',
        'flow_date',
        'cashless',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function toArray($request)
    {
        $data = parent::toArray($request);
        $data['amount'] = $this->resource->undoNormalizePrice();
        $data['created_at'] = $this->created_at->toDateTimeString();
        $data['updated_at'] = $this->updated_at->toDateTimeString();
        $data['deleted_at'] = ($this->deleted_at == null) ? null :
            $this->deleted_at->toDateTimeString();

        // if there is balanced data and there are really balanced records (not 1 record only)
        if (isset($this->balanced_summary) && $this->balanced_summary->balanced_records > 1) {
            $data['balanced'] = true;
            $amount = $this->balanced_summary->sum;
            $data['amount'] = denormalize_price(abs($amount));
            $data['direction'] = $amount < 0 ? $this->resource::DIRECTION_OUT : $this->resource::DIRECTION_IN;
        } else {
            $data['balanced'] = false;
        }

        return $data;
    }
}
