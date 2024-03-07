<?php

namespace App\Modules\SaleInvoice\Services\Builders;

use App\Http\Requests\Request;
use App\Interfaces\BuilderCorrection;
use App\Models\Db\InvoiceRegistry;
use App\Models\Db\User;
use App\Models\Other\ModuleType;

class Correction extends CreateInvoice implements BuilderCorrection
{
    /**
     * Create new invoice.
     *
     * @param Request $request
     * @param InvoiceRegistry $registry
     * @param User $user
     */
    public function create(Request $request, InvoiceRegistry $registry, User $user)
    {
        $invoice_data = $this->initCreate($request, $registry, $user);

        $invoice_data['corrected_invoice_id'] = $this->request->input('corrected_invoice_id');
        $invoice_data['correction_type'] = $this->request->input('correction_type');
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
            $item_data['is_correction'] = true;
            $item_data['position_corrected_id'] = $item['position_corrected_id'] ?? null;
            $this->invoice->items()->create($item_data);
        });
    }

    /**
     * Make copy of company data.
     */
    public function copyInvoiceCompanyData()
    {
        $this->saveInvoiceCompany(
            $this->invoice->correctedInvoice->invoiceCompany->toArray(),
            optional($this->invoice->bankAccount)->bank_name,
            optional($this->invoice->bankAccount)->number
        );
    }

    /**
     * Make copy of contractor data.
     */
    public function copyInvoiceContractorData()
    {
        $this->saveInvoiceContractor($this->invoice->correctedInvoice->invoiceContractor->toArray());
    }

    /**
     * Adding delivery address if application setting enabled.
     */
    public function setDeliveryAddress()
    {
        // Adding delivery address
        if ($this->user->selectedCompany()
            ->appSettings(ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED)
        ) {
            $invoice_corrected = $this->invoice->correctedInvoice;
            if (! empty($invoice_corrected->delivery_address_id)) {
                $this->delivery_address->addDeliveryAddress(
                    $this->invoice,
                    $invoice_corrected->deliveryAddress->id,
                    $invoice_corrected->default_delivery
                );
            }
        }
    }

    /**
     * Set parent document.
     */
    public function setParent()
    {
        $this->invoice->nodeInvoices()->attach($this->request->input('corrected_invoice_id'));
    }

    /**
     * Make billing for invoice.
     */
    public function makeTaxesBilling()
    {
        $this->createInvoiceTaxReport();
    }

    /**
     * Set parent document.
     */
    public function setDocument()
    {
        if ($this->toInvoiceFromBaseDocument()) {
            $corrected_invoice = $this->invoice->findOrFail($this->request->input('corrected_invoice_id'));
            if ($corrected_invoice->has('receipts')->count()) {
                $this->invoice->receipts()->attach($corrected_invoice->receipts()->pluck('id')->toArray());
            } else {
                $this->invoice->onlineSales()->attach($corrected_invoice->onlineSales()->pluck('id')->toArray());
            }
        }

        if ($this->payment_method::paymentInAdvance($this->invoice->paymentMethod->id)) {
            $this->payment->create($this->invoice, $this->user);
            $this->cashflow->addDuringIssuingInvoice($this->invoice, $this->user);
        }
    }

    /**
     * Update delivery address during updating invoice.
     */
    public function updateDeliveryAddress()
    {
        $invoice_corrected = $this->invoice->correctedInvoice;
        if (! empty($invoice_corrected->delivery_address_id)) {
            $this->delivery_address->updateDeliveryAddress(
                $this->invoice,
                $this->user,
                $invoice_corrected->deliveryAddress->id,
                $invoice_corrected->default_delivery
            );
        }
    }

    /**
     * Check if issuing correction to invoice from base document.
     *
     * @return bool
     */
    protected function toInvoiceFromBaseDocument()
    {
        return $this->invoice->select('invoices.id')
                ->where('company_id', $this->user->getSelectedCompanyId())
                ->where('id', $this->request->input('corrected_invoice_id'))
                ->where(function ($query) {
                    $query->has('receipts')
                        ->orHas('onlineSales');
                })->first() !== null;
    }
}
