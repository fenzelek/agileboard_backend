<?php

namespace App\Modules\SaleInvoice\Services\Builders;

use App\Http\Requests\Request;
use App\Interfaces\BuilderProforma;
use App\Models\Db\InvoiceRegistry;
use App\Models\Db\User;

class Proforma extends CreateInvoice implements BuilderProforma
{
    /**
     * Create invoice.
     *
     * @param Request $request
     * @param InvoiceRegistry $registry
     * @param User $user
     */
    public function create(Request $request, InvoiceRegistry $registry, User $user)
    {
        $invoice_data = $this->initCreate($request, $registry, $user);

        $invoice_data['payment_left'] = normalize_price($this->request->input('price_gross'));
        $invoice_data['sale_date'] = $this->request->input('sale_date');

        $this->invoice = $this->model_invoice->create($invoice_data);
    }

    /**
     * Create new invoice items in database.
     */
    public function addItems()
    {
        $items = collect($this->request->input('items'));
        $items->each(function ($item) {
            $item_data = $this->parseItemData($item);
            $this->invoice->items()->create($item_data);
        });
    }

    /**
     * Make copy of company data.
     */
    public function copyInvoiceCompanyData()
    {
        $this->saveInvoiceCompany($this->invoice->company->toArray());
    }

    /**
     * Make copy of contractor data.
     */
    public function copyInvoiceContractorData()
    {
        $this->saveInvoiceContractor($this->invoice->contractor->toArray());
    }

    /**
     * Adding delivery address if application setting enabled.
     */
    public function setDeliveryAddress()
    {
        parent::setDeliveryAddress();
    }

    /**
     * Make billing for invoice.
     */
    public function makeTaxesBilling()
    {
        $this->createInvoiceTaxReport();
    }
}
