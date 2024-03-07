<?php

namespace App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers;

use App\Models\Db\InvoiceItem as InvoiceItemModel;
use App\Models\Other\InvoiceCorrectionType;

class CorrectedInvoiceItem
{
    /**
     * @var InvoiceItem
     */
    private $invoiceItem;

    /**
     * InvoiceItemModel constructor.
     */
    public function __construct(InvoiceItemModel $invoiceItem)
    {
        $this->invoiceItem = $invoiceItem;
    }

    public function getRealRawVat()
    {
        if ($this->invoiceItem->positionCorrected && $this->invoiceItem->invoice->correction_type == InvoiceCorrectionType::TAX) {
            return (int) round($this->invoiceItem->positionCorrected->vat_sum * 1000.0 / $this->invoiceItem->positionCorrected->quantity);
        }

        return 0;
    }
}
