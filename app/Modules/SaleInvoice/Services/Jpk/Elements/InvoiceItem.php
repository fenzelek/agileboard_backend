<?php

namespace App\Modules\SaleInvoice\Services\Jpk\Elements;

use App\Models\Db\Invoice as InvoiceModel;
use App\Models\Db\InvoiceItem as InvoiceItemModel;
use App\Models\Db\VatRate;
use App\Models\Other\SaleInvoice\Jpk\Attribute;
use App\Models\Other\SaleInvoice\Jpk\Element;
use App\Modules\SaleInvoice\Services\Jpk\ElementAdder;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers\InvoiceItem as HelperInvoiceItem;

class InvoiceItem
{
    use ElementAdder;

    /**
     * Default type for invoice item.
     */
    const INVOICE_ITEM_TYPE = 'G';

    /**
     * @var InvoiceItem
     */
    protected $item;

    /**
     * @var Invoice
     */
    protected $invoice;

    /**
     * @var HelperInvoiceItem
     */
    protected $helper_invoice_item;

    public function __construct(HelperInvoiceItem $helper_invoice_item)
    {
        $this->helper_invoice_item = $helper_invoice_item;
    }

    /**
     * Create invoice item block.
     *
     * @param InvoiceItemModel $item
     * @param InvoiceModel $invoice
     *
     * @return Element|null
     */
    public function create(InvoiceItemModel $item, InvoiceModel $invoice)
    {
        $invoice_row = new Element('tns:FakturaWiersz');
        $invoice_row->addAttribute(new Attribute('typ', static::INVOICE_ITEM_TYPE));
        $this->setParentElement($invoice_row);

        $this->item = $item;
        $this->invoice = $invoice;
        $this->helper_invoice_item->item = $item;

        $this->addInvoiceNumber();
        $this->addName();
        $this->addUnit();
        $this->addQuantity();
        $this->addUnitNetPrice();
        $this->addUnitGrossPrice();
        $this->addTotalNetPrice();
        $this->addVatRate();

        return $this->getParentElement();
    }

    /**
     * Add invoice number.
     */
    protected function addInvoiceNumber()
    {
        $this->addChildElement(new Element('tns:P_2B', $this->invoice->number));
    }

    /**
     * Add service name.
     */
    protected function addName()
    {
        $this->addChildElement(new Element('tns:P_7', $this->item->print_name));
    }

    /**
     * Add unit.
     */
    protected function addUnit()
    {
        $this->addChildElement(new Element('tns:P_8A', $this->item->serviceUnit->slug));
    }

    /**
     * Add quantity.
     */
    protected function addQuantity()
    {
        $this->addChildElement(new Element(
            'tns:P_8B',
            number_format(denormalize_quantity($this->item->quantity), 4, '.', '')
        ));
    }

    /**
     * Add unit net price.
     */
    protected function addUnitNetPrice()
    {
        $this->addChildElement(new Element(
            'tns:P_9A',
            number_format_output($this->helper_invoice_item->getRealRawNetPrice(), '.')
        ));
    }

    /**
     * Add unit gross price.
     */
    protected function addUnitGrossPrice()
    {
        $this->addChildElement(new Element(
            'tns:P_9B',
            number_format_output($this->helper_invoice_item->getRealRawBruttoPrice(), '.')
        ));
    }

    /**
     * Add total net price.
     */
    protected function addTotalNetPrice()
    {
        $this->addChildElement(new Element(
            'tns:P_11',
            number_format_output($this->helper_invoice_item->getRealRawNetPriceSum(), '.')
        ));
    }

    /**
     * Add vat rate.
     */
    protected function addVatRate()
    {
        $this->addChildElement(new Element('tns:P_12', $this->getVatRate($this->item->vatRate)));
    }

    /**
     * Calculate valid value based on given vat rate.
     *
     * @param VatRate $vat_rate
     *
     * @return string
     */
    protected function getVatRate(VatRate $vat_rate)
    {
        if ($vat_rate->slug == VatRate::ZW) {
            return 'zw';
        }

        return $vat_rate->rate ? (string) $vat_rate->rate : '';
    }
}
