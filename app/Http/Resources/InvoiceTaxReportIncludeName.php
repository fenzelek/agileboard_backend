<?php

namespace App\Http\Resources;

class InvoiceTaxReportIncludeName extends AbstractResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,
            'vat_rate_id' => $this->vat_rate_id,
            'vat_rate_name' => $this->vatRate->name,
            'vat_sum' => denormalize_price($this->price_gross - $this->price_net),
            'price_net' => $this->resource->undoNormalizePriceNet(),
            'price_gross' => $this->resource->undoNormalizePriceGross(),
        ];
    }
}
