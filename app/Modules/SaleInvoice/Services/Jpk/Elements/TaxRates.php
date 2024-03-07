<?php

namespace App\Modules\SaleInvoice\Services\Jpk\Elements;

use App\Models\Other\SaleInvoice\Jpk\Element;
use App\Modules\SaleInvoice\Services\Jpk\ElementAdder;

class TaxRates
{
    use ElementAdder;

    /**
     * Create tax rates.
     *
     * @return Element|null
     */
    public function create()
    {
        $this->setParentElement(new Element('tns:StawkiPodatku'));

        $this->addTaxRate('tns:Stawka1', 0.23);
        $this->addTaxRate('tns:Stawka2', 0.08);
        $this->addTaxRate('tns:Stawka3', 0.05);
        $this->addTaxRate('tns:Stawka4', 0);
        $this->addTaxRate('tns:Stawka5', 0);

        return $this->getParentElement();
    }

    /**
     * Add tax rate field.
     *
     * @param string $field
     * @param float|int $value
     */
    protected function addTaxRate($field, $value)
    {
        $this->addChildElement(new Element($field, number_format($value, 2)));
    }
}
