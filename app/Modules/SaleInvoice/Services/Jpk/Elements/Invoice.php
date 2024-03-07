<?php

namespace App\Modules\SaleInvoice\Services\Jpk\Elements;

use App\Models\Db\Invoice as InvoiceModel;
use App\Models\Db\Company as CompanyModel;
use App\Models\Other\InvoiceCorrectionType;
use App\Models\Other\SaleInvoice\Jpk\Attribute;
use App\Models\Other\SaleInvoice\Jpk\Element;
use App\Modules\SaleInvoice\Services\Jpk\ElementAdder;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers\Address;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers\InvoiceType;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers\MarginProcedure;
use Carbon\Carbon;

class Invoice
{
    use ElementAdder;

    /**
     * Invoice type.
     */
    const TYPE = 'G';

    /**
     * @var InvoiceModel
     */
    protected $invoice;

    /**
     * @var InvoiceTaxes
     */
    protected $invoice_taxes_creator;

    /**
     * @var Address
     */
    protected $address;

    /**
     * @var Company
     */
    protected $company;

    /**
     * @var InvoiceType
     */
    protected $invoice_type;

    /**
     * @var MarginProcedure
     */
    protected $margin_procedure;

    /**
     * Invoice constructor.
     *
     * @param InvoiceTaxes $invoice_taxes_creator
     * @param Address $address
     * @param InvoiceType $invoice_type
     * @param MarginProcedure $margin_procedure
     */
    public function __construct(
        InvoiceTaxes $invoice_taxes_creator,
        Address $address,
        InvoiceType $invoice_type,
        MarginProcedure $margin_procedure
    ) {
        $this->invoice_taxes_creator = $invoice_taxes_creator;
        $this->address = $address;
        $this->invoice_type = $invoice_type;
        $this->margin_procedure = $margin_procedure;
    }

    /**
     * Create invoice block fields.
     *
     * @param InvoiceModel $invoice
     * @param CompanyModel $company
     *
     * @return Element|null
     */
    public function create(InvoiceModel $invoice, CompanyModel $company)
    {
        $invoice_element = new Element('tns:Faktura');
        $invoice_element->addAttribute(new Attribute('typ', static::TYPE));
        $this->setParentElement($invoice_element);

        $this->invoice = $invoice;
        $this->company = $company;

        $this->addIssueDate();
        $this->addNumber();
        $this->addContractorName();
        $this->addContractorAddress();
        $this->addCompanyName();
        $this->addCompanyAddress();
        $this->addCompanyVatInPrefix();
        $this->addCompanyVatIn();
        $this->addContractorVatInPrefix();
        $this->addContractorVatIn();
        $this->addSaleDate();
        $this->addTaxes();
        $this->addGrossPrice();
        $this->addBookingType();
        $this->addSelfInvoicing();
        $this->addReverseCharge();
        $this->addNotHavingTaxField();
        $this->addDebtsField();
        $this->addTaxCompanyField();
        $this->add3WayReverseChargeField();
        $this->addTourismMarginField();
        $this->addUsedProductArtOrAntiqueMarginFields();
        $this->addInvoiceTypeField();
        $this->addCorrectionFields();
        $this->addAdvanceFields();

        return $this->getParentElement();
    }

    /**
     * Add issue date.
     */
    protected function addIssueDate()
    {
        $this->addChildElement(new Element('tns:P_1', $this->invoice->issue_date));
    }

    /**
     * Add invoice number.
     */
    protected function addNumber()
    {
        $this->addChildElement(new Element('tns:P_2A', $this->invoice->number));
    }

    /**
     * Add contractor name.
     */
    protected function addContractorName()
    {
        $this->addChildElement(new Element('tns:P_3A', $this->invoice->invoiceContractor->name));
    }

    /**
     * Add contractor address.
     */
    protected function addContractorAddress()
    {
        $this->addChildElement(new Element(
            'tns:P_3B',
            $this->address->getContractorAddress($this->invoice->invoiceContractor)
        ));
    }

    /**
     * Add company name.
     */
    protected function addCompanyName()
    {
        $this->addChildElement(new Element(
            'tns:P_3C',
            $this->invoice->invoiceCompany->name
        ));
    }

    /**
     * Add company address.
     */
    protected function addCompanyAddress()
    {
        $this->addChildElement(new Element(
            'tns:P_3D',
            $this->address->getCompanyAddress($this->invoice->invoiceCompany)
        ));
    }

    /**
     * Add company vatin prefix.
     */
    protected function addCompanyVatInPrefix()
    {
        if ($this->invoice->invoiceCompany->vatinPrefix) {
            $this->addChildElement(new Element(
                'tns:P_4A',
                $this->invoice->invoiceCompany->vatinPrefix->key
            ));
        }
    }

    /**
     * Add company vatin.
     */
    protected function addCompanyVatIn()
    {
        $this->addChildElement(new Element(
            'tns:P_4B',
            $this->invoice->invoiceCompany->vatin
        ));
    }

    /**
     * Add contractor vatin prefix.
     */
    protected function addContractorVatInPrefix()
    {
        if ($this->invoice->invoiceContractor->vatinPrefix) {
            $this->addChildElement(new Element(
                'tns:P_5A',
                $this->invoice->invoiceContractor->vatinPrefix->key
            ));
        }
    }

    /**
     * Add contractor vatin.
     */
    protected function addContractorVatIn()
    {
        $this->addChildElement(new Element(
            'tns:P_5B',
            $this->invoice->invoiceContractor->vatin
        ));
    }

    /**
     * Add sale date.
     */
    protected function addSaleDate()
    {
        $this->addChildElement(new Element('tns:P_6', $this->invoice->sale_date));
    }

    /**
     * Add gross price.
     */
    protected function addGrossPrice()
    {
        $this->addChildElement(new Element(
            'tns:P_15',
            number_format_output($this->invoice->price_gross, '.')
        ));
    }

    /**
     * Add booking type.
     */
    protected function addBookingType()
    {
        $this->addChildElement(new Element('tns:P_16', 'false'));
    }

    /**
     * Add taxes fields.
     */
    protected function addTaxes()
    {
        foreach ($this->invoice_taxes_creator->create($this->invoice) as $tax_field) {
            $this->addChildElement($tax_field);
        }
    }

    /**
     * Add self invoicing.
     */
    protected function addSelfInvoicing()
    {
        $self_invoicing = $this->invoice->invoiceCompany->vatin === $this->invoice->invoiceContractor->vatin;
        $this->addChildElement(new Element('tns:P_17', $self_invoicing ? 'true' : 'false'));
    }

    /**
     * Add reverse charge.
     */
    protected function addReverseCharge()
    {
        $value = $this->invoice->invoiceType->isReverseChargeType() ? 'true' : 'false';

        $this->addChildElement(new Element('tns:P_18', $value));
    }

    /**
     * Add not having tax.
     */
    protected function addNotHavingTaxField()
    {
        $this->addChildElement(new Element(
            'tns:P_19',
            $this->company->vat_payer ? 'false' : 'true'
        ));
    }

    /**
     * Add debts.
     */
    protected function addDebtsField()
    {
        $this->addChildElement(new Element('tns:P_20', 'false'));
    }

    /**
     * Add tax company.
     */
    protected function addTaxCompanyField()
    {
        $this->addChildElement(new Element('tns:P_21', 'false'));
    }

    /**
     * Add 3-way EU triple.
     */
    protected function add3WayReverseChargeField()
    {
        $value = $this->invoice->isEuTripleReverseCharge() ? 'true' : 'false';

        $this->addChildElement(new Element('tns:P_23', $value));
    }

    /**
     * Add tourism margin.
     */
    protected function addTourismMarginField()
    {
        $this->addChildElement(new Element(
            'tns:P_106E_2',
            $this->margin_procedure->isTourOperatorMargin($this->invoice) ? 'true' : 'false'
        ));
    }

    /**
     * Add used product/art/antique.
     */
    protected function addUsedProductArtOrAntiqueMarginFields()
    {
        $is_special = $this->margin_procedure->isUsedProductArtOrAntiqueMargin($this->invoice);
        $this->addChildElement(new Element('tns:P_106E_3', $is_special ? 'true' : 'false'));

        if ($is_special) {
            $this->addChildElement(new Element(
                'tns:P_106E_3A',
                $this->margin_procedure->getName($this->invoice->invoiceMarginProcedure->slug)
            ));
        }
    }

    /**
     * Add invoice type.
     */
    protected function addInvoiceTypeField()
    {
        $this->addChildElement(new Element(
            'tns:RodzajFaktury',
            $this->invoice_type->calculate($this->invoice->invoiceType)
        ));
    }

    /**
     * Add correction fields.
     */
    protected function addCorrectionFields()
    {
        if (! $this->invoice->invoiceType->isCorrectionType()) {
            return;
        }

        $this->addCorrectionReasonField();
        $this->addCorrectedInvoiceNumberField();
        $this->addCorrectedInvoicePeriodField();
    }

    /**
     * Add correction reason.
     */
    protected function addCorrectionReasonField()
    {
        $this->addChildElement(new Element(
            'tns:PrzyczynaKorekty',
            InvoiceCorrectionType::all($this->invoice->company)[$this->invoice->correction_type]
        ));
    }

    /**
     * Add corrected invoice number.
     */
    protected function addCorrectedInvoiceNumberField()
    {
        $this->addChildElement(new Element(
            'tns:NrFaKorygowanej',
            $this->invoice->correctedInvoice->number
        ));
    }

    /**
     * Add corrected invoice period.
     */
    protected function addCorrectedInvoicePeriodField()
    {
        $this->addChildElement(new Element('tns:OkresFaKorygowanej', 'Od ' . Carbon::parse($this->invoice->correctedInvoice->issue_date)->format('Y-m-d') . ' do ' . Carbon::parse($this->invoice->issue_date)->format('Y-m-d')));
    }

    /**
     * Add advance fields.
     */
    protected function addAdvanceFields()
    {
        if (! $this->invoice->invoiceType->isAdvanceType()) {
            return;
        }

        $this->addAdvanceZALZaplataField();
        $this->addAdvanceZALPodatekField();
    }

    /**
     *  Add ZALZaplata field.
     */
    protected function addAdvanceZALZaplataField()
    {
        $this->addChildElement(new Element(
            'tns:ZALZaplata',
            number_format_output($this->invoice->taxes()->sum('price_gross'), '.')
        ));
    }

    /**
     *  Add ZALPodatekField.
     */
    protected function addAdvanceZALPodatekField()
    {
        $this->addChildElement(new Element(
            'tns:ZALPodatek',
            number_format_output($this->invoice->taxes()->selectRaw('SUM(price_gross) - SUM(price_net) as vat_sum')->first()->vat_sum, '.')
        ));
    }
}
