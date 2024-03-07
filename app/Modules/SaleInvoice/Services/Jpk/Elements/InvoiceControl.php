<?php

namespace App\Modules\SaleInvoice\Services\Jpk\Elements;

use App\Models\Other\SaleInvoice\Jpk\Element;
use App\Modules\SaleInvoice\Services\Jpk\ElementAdder;
use Illuminate\Database\Eloquent\Collection;

class InvoiceControl
{
    use ElementAdder;

    /**
     * Create invoices control fields.
     *
     * @param Collection $invoices
     *
     * @return Element|null
     */
    public function create(Collection $invoices)
    {
        $this->setParentElement(new Element('tns:FakturaCtrl'));

        $this->addInvoiceNumber($invoices);
        $this->addInvoiceGrossPrice($invoices);

        return $this->getParentElement();
    }

    /**
     * Add invoice number field.
     *
     * @param Collection $invoices
     */
    protected function addInvoiceNumber(Collection $invoices)
    {
        $this->addChildElement(new Element('tns:LiczbaFaktur', $invoices->count()));
    }

    /**
     * Add invoice price gross summary.
     *
     * @param Collection $invoices
     */
    protected function addInvoiceGrossPrice(Collection $invoices)
    {
        $this->addChildElement(new Element(
            'tns:WartoscFaktur',
            number_format_output($invoices->sum('price_gross'), '.')
        ));
    }
}
