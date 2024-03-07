<?php

namespace App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers;

use App\Models\Db\InvoiceItem as ModelInvoiceItem;
use App\Models\Other\InvoiceCorrectionType;

class InvoiceItem
{
    /**
     * @var ModelInvoiceItem
     */
    public $item;

    /**
     * Calculate net price for single item.
     *
     * @return int
     */
    public function getRealRawNetPrice()
    {
        if ($this->item->positionCorrected && $this->item->invoice->correction_type == InvoiceCorrectionType::TAX) {
            return 0;
        }

        if ($this->item->price_net) {
            return $this->item->price_net;
        }

        return (int) round($this->item->price_net_sum * 1000.0 / $this->item->quantity);
    }

    /**
     * @return int
     */
    public function getRealRawBruttoPrice()
    {
        return $this->getRealRawNetPrice() + $this->getRealRawVat();
    }

    /**
     * Calculate net price for single item.
     *
     * @return int
     */
    public function getRealRawVat()
    {
        return (int) round($this->item->vat_sum * 1000.0 / $this->item->quantity) - (new CorrectedInvoiceItem($this->item))->getRealRawVat();
    }

    /**
     * Calculate sum net price for single item.
     *
     * @return int
     */
    public function getRealRawNetPriceSum()
    {
        if ($this->item->positionCorrected && $this->item->invoice->correction_type == InvoiceCorrectionType::TAX) {
            return 0;
        }

        return $this->item->price_net_sum;
    }
}
