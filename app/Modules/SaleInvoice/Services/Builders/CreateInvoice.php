<?php

namespace App\Modules\SaleInvoice\Services\Builders;

use App\Http\Requests\Request;
use App\Interfaces\BuilderCreateInvoice;
use App\Interfaces\BuilderUpdateInvoice;
use App\Models\Db\BankAccount;
use App\Models\Db\Company;
use App\Models\Db\CompanyService;
use App\Models\Db\Invoice as ModelInvoice;
use App\Models\Db\InvoiceCompany;
use App\Models\Db\InvoiceContractor;
use App\Models\Db\InvoicePayment;
use App\Models\Db\InvoiceRegistry;
use App\Models\Db\PaymentMethod;
use App\Models\Db\User;
use App\Models\Db\VatRate;
use App\Models\Other\ModuleType;
use App\Models\Other\PaymentMethodType;
use App\Models\Db\Contractor;
use App\Modules\SaleInvoice\Services\BaseDocument;
use App\Modules\SaleInvoice\Services\Billing;
use App\Modules\SaleInvoice\Services\Cashflow;
use App\Modules\SaleInvoice\Services\DeliveryAddress;
use App\Modules\SaleInvoice\Services\InvoiceNumber;
use App\Modules\SaleInvoice\Services\Payment;

abstract class CreateInvoice implements BuilderCreateInvoice, BuilderUpdateInvoice
{
    /**
     * @var ModelInvoice
     */
    protected $invoice;

    /**
     * @var ModelInvoice
     */
    protected $model_invoice;

    /**
     * @var InvoiceNumber
     */
    protected $invoice_number;

    /**
     * @var InvoiceCompany
     */
    protected $invoice_company;

    /**
     * @var InvoiceContractor
     */
    protected $invoice_contractor;

    /**
     * @var PaymentMethod
     */
    protected $payment_method;

    /**
     * @var CompanyService
     */
    protected $company_service;

    /**
     * @var BaseDocument
     */
    protected $base_document;

    /**
     * @var Billing
     */
    protected $billing;

    /**
     * @var VatRate
     */
    protected $vat_rate;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var InvoiceRegistry
     */
    protected $registry;

    /**
     * @var DeliveryAddress
     */
    protected $delivery_address;

    /**
     * @var Payment
     */
    protected $payment;

    /**
     * @var Cashflow;
     */
    protected $cashflow;

    /**
     * @var InvoicePayment
     */
    protected $invoice_payment;

    /**
     * CreateInvoice constructor.
     *
     * @param ModelInvoice $model_invoice
     * @param InvoiceNumber $invoice_number
     * @param InvoiceCompany $invoice_company
     * @param InvoiceContractor $invoice_contractor
     * @param PaymentMethod $payment_method
     * @param CompanyService $company_service
     * @param VatRate $vat_rate
     * @param BaseDocument $base_document
     * @param DeliveryAddress $delivery_address
     * @param Payment $payment
     * @param Billing $billing
     * @param Cashflow $cashflow
     */
    public function __construct(
        ModelInvoice $model_invoice,
        InvoiceNumber $invoice_number,
        InvoiceCompany $invoice_company,
        InvoiceContractor $invoice_contractor,
        InvoicePayment $invoice_payment,
        PaymentMethod $payment_method,
        CompanyService $company_service,
        VatRate $vat_rate,
        BaseDocument $base_document,
        DeliveryAddress $delivery_address,
        Payment $payment,
        Billing $billing,
        Cashflow $cashflow
    ) {
        $this->model_invoice = $model_invoice;
        $this->invoice_number = $invoice_number;
        $this->invoice_company = $invoice_company;
        $this->invoice_contractor = $invoice_contractor;
        $this->invoice_payment = $invoice_payment;
        $this->payment_method = $payment_method;
        $this->company_service = $company_service;
        $this->vat_rate = $vat_rate;
        $this->base_document = $base_document;
        $this->delivery_address = $delivery_address;
        $this->payment = $payment;
        $this->billing = $billing;
        $this->cashflow = $cashflow;
    }

    /**
     * Init creation invoice.
     *
     * @param Request $request
     * @param InvoiceRegistry $registry
     * @param User $user
     *
     * @return array
     */
    public function initCreate(Request $request, InvoiceRegistry $registry, User $user)
    {
        $this->user = $user;
        $this->request = $request;
        $this->registry = $registry;

        return $this->parseInvoiceData();
    }

    /**
     * Create invoice.
     *
     * @param Request $request
     * @param InvoiceRegistry $registry
     * @param User $user
     */
    abstract public function create(Request $request, InvoiceRegistry $registry, User $user);

    /**
     * Create new invoice items in database.
     */
    abstract public function addItems();

    /**
     * Make copy of company data.
     */
    abstract public function copyInvoiceCompanyData();

    /**
     * Make copy of contractor data.
     */
    abstract public function copyInvoiceContractorData();

    /**
     * Adding delivery address if application setting enabled.
     */
    public function setDeliveryAddress()
    {
        // Adding delivery address
        if ($this->user->selectedCompany()
            ->appSettings(ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED)
        ) {
            $this->delivery_address->addDeliveryAddress(
                $this->invoice,
                $this->request->input('delivery_address_id'),
                $this->request->input('default_delivery')
            );
        }
    }

    /**
     * Set source document for invoice.
     */
    public function setDocument()
    {
        if ($this->base_document->creatingFromBaseDocument($this->request->input('extra_item_type'))) {
            $this->base_document->setFromBaseDocument(
                $this->invoice,
                $this->user,
                $this->request->input('extra_item_type'),
                $this->request->input('extra_item_id')
            );
        } elseif ($this->payment_method::paymentInAdvance($this->invoice->paymentMethod->id)) {
            if (! $this->request->input('special_payment')) {
                $this->payment->create($this->invoice, $this->user);
                $this->cashflow->addDuringIssuingInvoice($this->invoice, $this->user);
            }
        }
    }

    /**
     * Set parent document.
     */
    public function setParent()
    {
    }

    /**
     * Return saved invoice.
     *
     * @return ModelInvoice
     */
    public function getInvoice(): ModelInvoice
    {
        return $this->invoice;
    }

    /**
     * Init updating invoice.
     *
     * @param Request $request
     * @param ModelInvoice $invoice
     * @param User $user
     */
    public function initUpdate(Request $request, ModelInvoice $invoice, User $user)
    {
        $this->user = $user;
        $this->request = $request;
        $this->invoice = $invoice;
    }

    /**
     * Update company data during updating invoice.
     */
    public function updateWithCurrentCompanyData()
    {
        $company = Company::find($this->user->getSelectedCompanyId());

        if ($company) {
            $bank_account = BankAccount::find($this->request->input('bank_account_id'));
            $bank_name = optional($bank_account)->bank_name;
            $bank_account_number = optional($bank_account)->number;
            $this->invoice->invoiceCompany->update(array_merge(
                $company->toArray(),
                compact('bank_name'),
                compact('bank_account_number')
            ));
        }
    }

    /**
     * Update contractor data during updating invoice.
     */
    public function updateWithCurrentContractorData()
    {
        $contractor = Contractor::inCompany($this->user)
            ->find($this->request->input('contractor_id'));

        if ($contractor) {
            $this->invoice->invoiceContractor->update($contractor->toArray() +
                ['contractor_id' => $contractor->id]);

            $this->invoice->contractor_id = $contractor->id;
        }
    }

    /**
     * Update delivery address during updating invoice.
     */
    public function updateDeliveryAddress()
    {
        $this->delivery_address->updateDeliveryAddress(
            $this->invoice,
            $this->user,
            $this->request->input('delivery_address_id'),
            $this->request->input('default_delivery')
        );
    }

    /**
     * Update invoice.
     */
    public function update()
    {
        if ($this->base_document->notBelongs($this->invoice)) {
            $this->updatePaymentAndCashflow();

            $this->invoice->items()->each(function ($item) {
                $item->companyService->decrement('is_used');
                $item->delete();
            });

            $this->addItems();

            $this->updateTaxes();

            $this->updateSaleDate();
            $this->updateAmountsAndDates();
        }

        $this->invoice->issue_date = $this->request->input('issue_date');
        $this->invoice->save();
    }

    /**
     * Creating special partial payment.
     */
    public function createSpecialPayment()
    {
        $partial_payment_amount = $this->request->input('special_payment.amount');
        $partial_payment_method_id = $this->request->input('special_payment.payment_method_id');

        // Adding partial payments if it was passed
        if (! empty($this->request->input('special_payment'))) {
            $this->invoice_payment->create([
                'invoice_id' => $this->invoice->id,
                'amount' => normalize_price($partial_payment_amount),
                'payment_method_id' => $partial_payment_method_id,
                'special_partial_payment' => true,
                'registrar_id' => $this->user->id,
            ]);

            $this->cashflow->in(
                $this->invoice,
                $this->user,
                PaymentMethod::find($partial_payment_method_id),
                normalize_price($partial_payment_amount)
            );

            if (PaymentMethod::paymentInAdvance($this->request->input('payment_method_id'))) {
                $second_payment_amount = $this->request->input('price_gross') -
                    $partial_payment_amount;

                $this->invoice_payment->create([
                    'invoice_id' => $this->invoice->id,
                    'amount' => normalize_price($second_payment_amount),
                    'payment_method_id' => $this->request->input('payment_method_id'),
                    'special_partial_payment' => true,
                    'registrar_id' => $this->user->id,
                ]);

                $this->cashflow->in(
                    $this->invoice,
                    $this->user,
                    PaymentMethod::find($this->request->input('payment_method_id')),
                    normalize_price($second_payment_amount)
                );
            }
            $this->invoice->update([
                'payment_left' => $this->billing->countPaymentLeft($this->invoice),
            ]);
        }
    }

    /**
     * Updating or deleting special partial payment.
     */
    public function updateSpecialPayment()
    {
        // We don't need to remove special payments here because there will be removed before
        // see Billing@shouldModified and place where it is called

        // Adding partial payment if it was passed
        if (! empty($this->request->input('special_payment'))) {
            $this->createSpecialPayment();
        }
    }

    /**
     * Save copy of company data.
     *
     * @param $company
     */
    protected function saveInvoiceCompany($company, $bank_name = null, $bank_account_number = null)
    {
        $this->invoice_company->create(
            array_merge($company, [
                'invoice_id' => $this->invoice->id,
                'company_id' => $this->invoice->company_id,
            ], compact('bank_name'), compact('bank_account_number'))
        );
    }

    /**
     * Save copy of contractor data.
     *
     * @param array $contractor
     */
    protected function saveInvoiceContractor($contractor)
    {
        $this->invoice_contractor->create(
            array_merge($contractor, [
                'invoice_id' => $this->invoice->id,
                'contractor_id' => $this->invoice->contractor_id,
            ])
        );
    }

    /**
     * Create new invoice tax to report in database.
     */
    protected function createInvoiceTaxReport()
    {
        $this->storeInvoiceTaxes($this->request->input('taxes'));
    }

    /**
     * Parse invoice item data from request.
     *
     * @param $item
     *
     * @return array
     */
    protected function parseItemData($item)
    {
        $company_service = $this->company_service
            ->where('id', $item['company_service_id'])
            ->first();
        $vat_rate = $this->vat_rate
            ->where('id', $item['vat_rate_id'])
            ->first();
        $item_data = [
            'company_service_id' => $company_service->id,
            'pkwiu' => $company_service->pkwiu,
            'name' => $company_service->name,
            'type' => $company_service->type,
            'price_net_sum' => normalize_price($item['price_net_sum']),
            'price_gross_sum' => normalize_price($item['price_gross_sum']),
            'vat_rate' => $vat_rate->rate,
            'vat_rate_id' => $vat_rate->id,
            'vat_sum' => normalize_price($item['vat_sum']),
            'quantity' => normalize_quantity($item['quantity']),
            'service_unit_id' => $item['service_unit_id'],
            'print_on_invoice' => $company_service->print_on_invoice,
            'description' => $company_service->description,
            'creator_id' => $this->user->id,
        ];
        // For collective invoice add base_document_id to item to match it with receipt/online sale
        if ($this->invoice->isBilling() && ! empty($item['base_document_id'])) {
            $item_data['base_document_id'] = $item['base_document_id'];
        }
        if ($this->invoice->gross_counted) {
            $item_data['price_gross'] = normalize_price($item['price_gross']);
        } else {
            $item_data['price_net'] = normalize_price($item['price_net']);
        }

        // Check if company can use custom names for invoices_items
        if ($this->user->selectedCompany()
            ->appSettings(ModuleType::INVOICES_SERVICES_NAME_CUSTOMIZE)
        ) {
            $item_data['custom_name'] = empty(trim($item['custom_name'])) ?
                null : trim($item['custom_name']);
        }

        $company_service->increment('is_used');
        $company_service->save();

        return $item_data;
    }

    /**
     * Parse invoice data from request.
     *
     * @return array
     */
    protected function parseInvoiceData()
    {
        $order_number = $this->invoice_number
            ->findFirstEmptyOrderNumber($this->request, $this->registry, $this->user);

        $invoice_data = [
            'number' => $this->invoice_number->parseInvoiceNumber(
                $this->request,
                $this->registry,
                $order_number
            ),
            'order_number' => $order_number,
            'invoice_registry_id' => $this->registry->id,
            'drawer_id' => $this->user->id,
            'company_id' => $this->user->getSelectedCompanyId(),
            'contractor_id' => $this->request->input('contractor_id'),
            'issue_date' => $this->request->input('issue_date'),
            'order_number_date' => $this->request->input('issue_date'),
            'invoice_type_id' => $this->request->input('invoice_type_id'),
            'price_net' => normalize_price($this->request->input('price_net')),
            'price_gross' => normalize_price($this->request->input('price_gross')),
            'vat_sum' => normalize_price($this->request->input('vat_sum')),
            'payment_term_days' => $this->request->input('payment_term_days'),
            'payment_method_id' => $this->request->input('payment_method_id'),
            'bank_account_id' => $this->request->input('bank_account_id') ?: null,
            'paid_at' => null,
            'gross_counted' => $this->request->input('gross_counted'),
            'description' => $this->request->input('description'),
        ];

        /** sometimes its an array and sometimes string so be carefoul from php 7.2 string is not countable */
        $extra_item_id = $this->request->input('extra_item_id');

        if (is_array($extra_item_id) && count($extra_item_id) > 1) {
            $invoice_data['payment_method_id'] = $this->payment_method
                ->findBySlug(PaymentMethodType::OTHER)->id;
            $invoice_data['sale_date'] = null;
        }

        return $invoice_data;
    }

    /**
     * Update sale date if invoice collect many receipts.
     */
    protected function updateSaleDate()
    {
        if (! $this->invoice->isCollective()) {
            $this->invoice->sale_date = $this->request->input('sale_date');
        }
    }

    /**
     * Update amounts and dates during updating invoice.
     */
    protected function updateAmountsAndDates()
    {
        $this->invoice->price_net = normalize_price($this->request->input('price_net'));
        $this->invoice->price_gross = normalize_price($this->request->input('price_gross'));
        $this->invoice->vat_sum = normalize_price($this->request->input('vat_sum'));
        $this->invoice->payment_left = $this->billing->countPaymentLeft($this->invoice);
        $this->invoice->payment_term_days = $this->request->input('payment_term_days');
        $this->invoice->payment_method_id = $this->request->input('payment_method_id');
        $this->invoice->bank_account_id = $this->request->input('bank_account_id') ?: null;
        $this->invoice->gross_counted = $this->request->input('gross_counted');
        $this->invoice->description = $this->request->input('description');

        if ($this->billing->completed($this->invoice)) {
            $this->invoice->paid_at = $this->invoice->payments()->latest()->first()->created_at;
        }
    }

    /**
     * Update payment and cashflow.
     */
    protected function updatePaymentAndCashflow()
    {
        $price_gross = normalize_price($this->request->input('price_gross'));
        $payment_method = $this->payment_method::find($this->request->input('payment_method_id'));
        if ($this->billing->shouldModified(
            $this->invoice,
            $payment_method,
            $price_gross,
            $this->payment->paymentInAdvance($payment_method->id),
            $this->request->input('special_payment')
        )
        ) {
            $this->payment->remove($this->invoice);
            $this->cashflow->out($this->invoice, $this->user);

            if ($this->payment->paymentInAdvance($payment_method->id) &&
                ! $this->specialPaymentWasSent()) {
                $this->payment->add($this->invoice, $this->user, $payment_method, $price_gross);
                $this->invoice->payment_left = 0;
                $this->cashflow->in($this->invoice, $this->user, $payment_method, $price_gross);
            }
        }
    }

    /**
     * Store Invoice Taxes.
     *
     * @param array $taxes
     * @param string $tax_relation
     */
    protected function storeInvoiceTaxes(array $taxes, $tax_relation = 'taxes')
    {
        if (! method_exists($this->invoice, $tax_relation)) {
            return;
        }
        $items = collect($taxes);
        $items->each(function ($item) use ($tax_relation) {
            $vat_rate = $this->vat_rate
                ->where('id', $item['vat_rate_id'])
                ->first();
            $this->invoice->{$tax_relation}()->create([
                'vat_rate_id' => $vat_rate->id,
                'price_net' => normalize_price($item['price_net']),
                'price_gross' => normalize_price($item['price_gross']),
            ]);
        });
    }

    /**
     * Update taxes.
     */
    protected function updateTaxes()
    {
        $this->invoice->taxes()->delete();
        $this->createInvoiceTaxReport();
    }

    /**
     * Verify whether special payment was sent.
     *
     * @return bool
     */
    protected function specialPaymentWasSent()
    {
        return ! empty($this->request->input('special_payment'));
    }
}
