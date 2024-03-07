<?php

namespace App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers;

use App\Models\Db\Invoice as InvoiceModel;
use App\Models\Other\InvoiceMarginProcedureType;

class MarginProcedure
{
    /**
     * Verify whether invoice is margin/margin correction used product, antique or art.
     *
     * @param InvoiceModel $invoice_model
     *
     * @return bool
     */
    public function isUsedProductArtOrAntiqueMargin(InvoiceModel $invoice_model)
    {
        return $invoice_model->invoiceType->isMarginType() &&
            in_array($invoice_model->invoiceMarginProcedure->slug, $this->getSpecialMarginTypes());
    }

    /**
     * Verify whether invoice is margin/margin correction tourism.
     *
     * @param InvoiceModel $invoice_model
     *
     * @return bool
     */
    public function isTourOperatorMargin(InvoiceModel $invoice_model)
    {
        return $invoice_model->invoiceType->isMarginType() &&
            $invoice_model->invoiceMarginProcedure->slug ==
            InvoiceMarginProcedureType::TOUR_OPERATOR;
    }

    /**
     * Get name of invoice margin procedure.
     *
     * @param string $invoice_margin_procedure_slug
     *
     * @return string
     */
    public function getName($invoice_margin_procedure_slug)
    {
        switch ($invoice_margin_procedure_slug) {
            case InvoiceMarginProcedureType::USED_PRODUCT:
                return 'procedura marży - towary używane';
            case InvoiceMarginProcedureType::ART:
                return 'procedura marży - dzieła sztuki';
            case InvoiceMarginProcedureType::ANTIQUE:
                return 'procedura marży - przedmioty kolekcjonerskie i antyki';
        }
    }

    /**
     * Get special margin types.
     *
     * @return array
     */
    protected function getSpecialMarginTypes()
    {
        return [
            InvoiceMarginProcedureType::USED_PRODUCT,
            InvoiceMarginProcedureType::ART,
            InvoiceMarginProcedureType::ANTIQUE,
        ];
    }
}
