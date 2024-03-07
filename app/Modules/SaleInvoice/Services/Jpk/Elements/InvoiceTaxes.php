<?php

namespace App\Modules\SaleInvoice\Services\Jpk\Elements;

use App\Models\Db\Invoice as InvoiceModel;
use App\Models\Db\VatRate;
use App\Modules\SaleInvoice\Services\Jpk\ElementAdder;
use Illuminate\Database\Eloquent\Collection;

class InvoiceTaxes
{
    use ElementAdder;

    /**
     * @var InvoiceTax
     */
    protected $tax_element;

    /**
     * @var Collection
     */
    protected $taxes;

    /**
     * @var InvoiceModel
     */
    protected $invoice;

    /**
     * InvoiceTaxes constructor.
     *
     * @param \App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceTax $tax_element
     */
    public function __construct(InvoiceTax $tax_element)
    {
        $this->tax_element = $tax_element;
    }

    /**
     * Create invoice taxes fields.
     *
     * @param InvoiceModel $invoice
     *
     * @return array
     */
    public function create(InvoiceModel $invoice)
    {
        $this->clearElements();

        $this->taxes = $invoice->taxes;
        $this->invoice = $invoice;

        $this->addBaseRateTaxesFields();
        $this->addFirstLowerRateTaxesFields();
        $this->addSecondLowerRateTaxesFields();
        $this->addThirdLowerRateTaxesFields();
        $this->addFourthLowerRateTaxesFields();
        $this->addZeroRateTaxesFields();
        $this->addExemptionRateTaxesFields();

        return $this->getElements();
    }

    /**
     * Add base rate taxes (23%, 22%).
     */
    protected function addBaseRateTaxesFields()
    {
        $this->addElements($this->tax_element->create(
            $this->taxes,
            [VatRate::TAX_22, VatRate::TAX_23],
            'tns:P_13_1',
            'tns:P_14_1'
        ));
    }

    /**
     *  Add first lower rate taxes (8%, 7%).
     */
    protected function addFirstLowerRateTaxesFields()
    {
        $this->addElements($this->tax_element->create(
            $this->taxes,
            [VatRate::TAX_7, VatRate::TAX_8],
            'tns:P_13_2',
            'tns:P_14_2'
        ));
    }

    /**
     * Add second lower rate taxes (5%).
     */
    protected function addSecondLowerRateTaxesFields()
    {
        $this->addElements($this->tax_element->create(
            $this->taxes,
            VatRate::TAX_5,
            'tns:P_13_3',
            'tns:P_14_3'
        ));
    }

    /**
     * Add second lower rate taxes - reverse charge.
     */
    protected function addThirdLowerRateTaxesFields()
    {
        if ($this->invoice->invoiceType->isReverseChargeType()) {
            $this->addElements($this->tax_element->create(
                $this->taxes,
                [VatRate::NP, VatRate::NP_UE],
                'tns:P_13_4',
                'tns:P_14_4'
            ));
        }
    }

    /**
     * Add second lower rate taxes - margin.
     */
    protected function addFourthLowerRateTaxesFields()
    {
        if ($this->invoice->invoiceType->isMarginType()) {
            $this->addElements($this->tax_element->create(
                $this->taxes,
                [VatRate::NP, VatRate::NP_UE],
                'tns:P_13_5',
                'tns:P_14_5'
            ));
        }
    }

    /**
     * Add zero rate taxes (0%).
     */
    protected function addZeroRateTaxesFields()
    {
        $this->addElements($this->tax_element->create(
            $this->taxes,
            [VatRate::TAX_0, VatRate::TAX_0_WDT, VatRate::TAX_0_EXP],
            'tns:P_13_6',
            null
        ));
    }

    /**
     * Add exemption rate taxes (zw.).
     */
    protected function addExemptionRateTaxesFields()
    {
        $this->addElements($this->tax_element->create(
            $this->taxes,
            VatRate::ZW,
            'tns:P_13_7',
            null
        ));
    }
}
