<?php

namespace App\Modules\SaleInvoice\Services\Jpk\Elements;

use App\Models\Other\SaleInvoice\Jpk\Element;
use App\Modules\SaleInvoice\Services\Jpk\ElementAdder;
use Illuminate\Database\Eloquent\Collection;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers\InvoiceItem as HelperInvoiceItem;

class InvoiceItemControl
{
    use ElementAdder;

    /**
     * @var Collection
     */
    protected $items;

    /**
     * @var HelperInvoiceItem
     */
    protected $helper_invoice_item;

    public function __construct(HelperInvoiceItem $helper_invoice_item)
    {
        $this->helper_invoice_item = $helper_invoice_item;
    }

    /**
     * Create invoice control block.
     *
     * @param Collection $invoices
     *
     * @return Element|null
     */
    public function create(Collection $invoices)
    {
        $this->setParentElement(new Element('tns:FakturaWierszCtrl'));

        $this->setItems($invoices);

        $this->addItemsNumbers();
        $this->addInvoiceItemsGrossPrice();

        return $this->getParentElement();
    }

    /**
     * Add invoice items number.
     */
    protected function addItemsNumbers()
    {
        $this->addChildElement(new Element('tns:LiczbaWierszyFaktur', $this->items->count()));
    }

    /**
     * Add invoice items gross price summary.
     */
    protected function addInvoiceItemsGrossPrice()
    {
        $sumRealRawNetPriceSum = $this->items->sum(function ($item) {
            $this->helper_invoice_item->item = $item;

            return $this->helper_invoice_item->getRealRawNetPriceSum();
        });
        $this->addChildElement(new Element(
            'tns:WartoscWierszyFaktur',
            number_format_output($sumRealRawNetPriceSum, '.')
        ));
    }

    /**
     * Set all invoices items in flat collection.
     *
     * @param Collection $invoices
     */
    protected function setItems(Collection $invoices)
    {
        $this->items = $invoices->pluck('items')->flatten(1);
    }
}
