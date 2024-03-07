<?php

namespace App\Modules\SaleReport\Services\Optima;

use App\Models\Db\Invoice;
use App\Models\Db\InvoiceTaxReport;
use App\Models\Db\VatRate;
use App\Models\Other\SaleReport\Optima\TaxItem;

class TaxesFiller
{
    // possible values for FLAGA_ fields
    const VAT_RATE_ZW = 1;
    const VAT_RATE_NP = 4;
    const VAT_RATE_OTHER = 0;

    /**
     * Calculate taxes for given invoice.
     *
     * @param Invoice $invoice
     *
     * @return array
     */
    public function calculate(Invoice $invoice)
    {
        $items = [];

        $invoice->taxes->each(function ($tax) use (&$items) {
            $items[] = $this->createNewTaxItem($tax);
        });

        return $items;
    }

    /**
     * Create new tax item based on given tax report.
     *
     * @param InvoiceTaxReport $tax_report
     *
     * @return TaxItem
     */
    protected function createNewTaxItem(InvoiceTaxReport $tax_report)
    {
        $type = $this->getType($tax_report);
        $vat_rate = $this->getVatRate($tax_report);
        $net_price = $this->getNetPrice($tax_report);
        $vat = $this->getVat($tax_report);

        return new TaxItem($vat_rate, $net_price, $vat, $type);
    }

    /**
     * Get FLAGA_ column value for given tax report.
     *
     * @param InvoiceTaxReport $tax_report
     *
     * @return int
     */
    private function getType(InvoiceTaxReport $tax_report)
    {
        switch ($tax_report->vatRate->name) {
            case VatRate::ZW:
                return static::VAT_RATE_ZW;
            case VatRate::NP:
                return static::VAT_RATE_NP;
            case VatRate::NP_UE:
                return static::VAT_RATE_NP;
        }

        return static::VAT_RATE_OTHER;
    }

    /**
     * Get vat rate.
     *
     * @param InvoiceTaxReport $tax_report
     *
     * @return int
     */
    private function getVatRate(InvoiceTaxReport $tax_report)
    {
        return $tax_report->vatRate->rate;
    }

    /**
     * Get net price.
     *
     * @param InvoiceTaxReport $tax_report
     *
     * @return mixed
     */
    private function getNetPrice(InvoiceTaxReport $tax_report)
    {
        return $tax_report->price_net;
    }

    /**
     * Get Vat amount.
     *
     * @param InvoiceTaxReport $tax_report
     *
     * @return mixed
     */
    private function getVat(InvoiceTaxReport $tax_report)
    {
        return $tax_report->price_gross - $tax_report->price_net;
    }
}
