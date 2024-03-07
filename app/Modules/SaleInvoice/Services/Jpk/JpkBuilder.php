<?php

namespace App\Modules\SaleInvoice\Services\Jpk;

use App\Models\Db\Company as CompanyModel;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Header;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Invoice;
use App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceControl;
use App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceItem;
use App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceItemControl;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Root;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Company;
use App\Modules\SaleInvoice\Services\Jpk\Elements\TaxRates;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class JpkBuilder
{
    use ElementAdder;

    /**
     * @var Root
     */
    protected $root;

    /**
     * @var Header
     */
    protected $header;

    /**
     * @var Company
     */
    protected $company_creator;

    /**
     * @var Invoice
     */
    protected $invoice_creator;

    /**
     * @var InvoiceControl
     */
    protected $invoice_control_creator;

    /**
     * @var TaxRates
     */
    protected $tax_rates_creator;

    /**
     * @var InvoiceItem
     */
    protected $invoice_item_creator;

    /**
     * @var InvoiceItemControl
     */
    protected $invoice_items_control_creator;

    /**
     * @var XmlBuilder
     */
    protected $xml_builder;

    /**
     * JpkBuilder constructor.
     *
     * @param Root $root
     * @param Header $header
     * @param Company $company_creator
     * @param Invoice $invoice_creator
     * @param InvoiceControl $invoice_control_creator
     * @param TaxRates $tax_rates_creator
     * @param InvoiceItem $invoice_item_creator
     * @param InvoiceItemControl $invoice_items_control_creator
     * @param XmlBuilder $xml_builder
     */
    public function __construct(
        Root $root,
        Header $header,
        Company $company_creator,
        Invoice $invoice_creator,
        InvoiceControl $invoice_control_creator,
        TaxRates $tax_rates_creator,
        InvoiceItem $invoice_item_creator,
        InvoiceItemControl $invoice_items_control_creator,
        XmlBuilder $xml_builder
    ) {
        $this->root = $root;
        $this->header = $header;
        $this->company_creator = $company_creator;
        $this->invoice_creator = $invoice_creator;
        $this->invoice_control_creator = $invoice_control_creator;
        $this->tax_rates_creator = $tax_rates_creator;
        $this->invoice_item_creator = $invoice_item_creator;
        $this->invoice_items_control_creator = $invoice_items_control_creator;
        $this->xml_builder = $xml_builder;
    }

    /**
     * Create JPK XML file.
     *
     * @param CompanyModel $company
     * @param Collection $invoices
     * @param $start_date
     * @param $end_date
     *
     * @return string
     */
    public function create(CompanyModel $company, Collection $invoices, $start_date, $end_date)
    {
        $this->initializeDocument()
            ->setHeader($company, $start_date, $end_date)
            ->setCompany($company)
            ->setInvoices($invoices, $company)
            ->setInvoicesControl($invoices)
            ->setTaxRates()
            ->setInvoicesItems($invoices)
            ->setInvoicesItemsControl($invoices);

        return $this->xml_builder->create($this->getParentElement());
    }

    /**
     * Initialize document.
     *
     * @return $this
     */
    protected function initializeDocument()
    {
        $this->setParentElement($this->root->create());

        return $this;
    }

    /**
     * Set header.
     *
     * @param CompanyModel $company
     * @param string $start_date
     * @param string $end_date
     *
     * @return $this
     */
    protected function setHeader(CompanyModel $company, $start_date, $end_date)
    {
        $this->addChildElement($this->header->create(
            $company,
            Carbon::parse($start_date),
            Carbon::parse($end_date)
        ));

        return $this;
    }

    /**
     * Set company.
     *
     * @param CompanyModel $company
     *
     * @return $this
     */
    protected function setCompany(CompanyModel $company)
    {
        $this->addChildElement($this->company_creator->create($company));

        return $this;
    }

    /**
     * Set invoices.
     *
     * @param Collection $invoices
     * @param CompanyModel $company
     *
     * @return $this
     */
    protected function setInvoices(Collection $invoices, CompanyModel $company)
    {
        $invoices->each(function ($invoice) use ($company) {
            $this->addChildElement($this->invoice_creator->create($invoice, $company));
        });

        return $this;
    }

    /**
     * Set invoices control.
     *
     * @param Collection $invoices
     *
     * @return $this
     */
    protected function setInvoicesControl(Collection $invoices)
    {
        $this->addChildElement($this->invoice_control_creator->create($invoices));

        return $this;
    }

    /**
     * Set tax rates.
     *
     * @return $this
     */
    protected function setTaxRates()
    {
        $this->addChildElement($this->tax_rates_creator->create());

        return $this;
    }

    /**
     * Set invoice items.
     *
     * @param Collection $invoices
     *
     * @return $this
     */
    protected function setInvoicesItems(Collection $invoices)
    {
        $invoices->each(function ($invoice) {
            $invoice->items->each(function ($item) use ($invoice) {
                $this->addChildElement($this->invoice_item_creator->create($item, $invoice));
            });
        });

        return $this;
    }

    /**
     * Set invoice items control.
     *
     * @param Collection $invoices
     *
     * @return $this
     */
    protected function setInvoicesItemsControl(Collection $invoices)
    {
        $this->addChildElement($this->invoice_items_control_creator->create($invoices));

        return $this;
    }
}
