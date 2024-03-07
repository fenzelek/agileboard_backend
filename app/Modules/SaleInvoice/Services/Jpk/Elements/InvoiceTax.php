<?php

namespace App\Modules\SaleInvoice\Services\Jpk\Elements;

use App\Models\Other\SaleInvoice\Jpk\Element;
use App\Modules\SaleInvoice\Services\Jpk\ElementAdder;
use Illuminate\Database\Eloquent\Collection;

class InvoiceTax
{
    use ElementAdder;

    /**
     * @var \Illuminate\Support\Collection
     */
    protected $taxes;

    /**
     * Create new tax fields.
     *
     * @param Collection $tax_reports
     * @param array|string $vat_rates
     * @param $net_field
     * @param null $tax_field
     *
     * @return array
     */
    public function create(Collection $tax_reports, $vat_rates, $net_field, $tax_field = null)
    {
        $this->clearElements();

        $this->setTaxes($tax_reports, $vat_rates);

        $this->setNetField($net_field);
        $this->setTaxField($tax_field);

        return $this->getElements();
    }

    /**
     * Set taxes based on given vat rates.
     *
     * @param Collection $tax_reports
     * @param array|string $vat_rates
     */
    protected function setTaxes(Collection $tax_reports, $vat_rates)
    {
        $vat_rates = (array) $vat_rates;

        $this->taxes = $tax_reports->filter(function ($report) use ($vat_rates) {
            return in_array($report->vatRate->name, $vat_rates);
        });
    }

    /**
     * Set net field.
     *
     * @param string $net_field
     */
    protected function setNetField($net_field)
    {
        if (! $this->taxes->count()) {
            return;
        }

        $value = number_format_output($this->taxes->sum('price_net'), '.');

        $this->addElement(new Element($net_field, $value));
    }

    /**
     * Set tax field.
     *
     * @param string|null $tax_field
     */
    protected function setTaxField($tax_field)
    {
        if ($tax_field === null || ! $this->taxes->count()) {
            return;
        }

        $value = number_format_output($this->taxes->sum('price_gross') -
            $this->taxes->sum('price_net'), '.');

        $this->addElement(new Element($tax_field, $value));
    }
}
