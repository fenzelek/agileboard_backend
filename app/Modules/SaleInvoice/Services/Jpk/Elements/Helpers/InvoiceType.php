<?php

namespace App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers;

use App\Models\Db\InvoiceType as InvoiceTypeModel;
use App\Models\Other\InvoiceTypeStatus;

class InvoiceType
{
    /**
     * Calculate JPK invoice type based on given invoice type.
     *
     * @param InvoiceTypeModel $invoice_type
     *
     * @return string
     */
    public function calculate(InvoiceTypeModel $invoice_type)
    {
        if ($invoice_type->isCorrectionType()) {
            return 'KOREKTA';
        }
        if ($invoice_type->isAdvanceType()) {
            return 'ZAL';
        }
        if ($this->isVatType($invoice_type) || $invoice_type->isSubtypeOf(InvoiceTypeStatus::VAT)) {
            return 'VAT';
        }

        return 'POZ';
    }

    /**
     * Verify whether invoice type is VAT type.
     *
     * @param InvoiceTypeModel $invoice_type
     *
     * @return bool
     */
    protected function isVatType(InvoiceTypeModel $invoice_type)
    {
        return in_array($invoice_type->slug, [
            InvoiceTypeStatus::VAT,
        ]);
    }
}
