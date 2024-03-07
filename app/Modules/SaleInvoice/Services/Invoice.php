<?php

namespace App\Modules\SaleInvoice\Services;

use App\Models\Db\Company;
use App\Models\Db\Invoice as ModelInvoice;
use App\Models\Db\InvoiceItem;
use App\Http\Requests\Request;
use App\Models\Db\InvoicePayment;
use App\Models\Db\ServiceUnit;
use App\Models\Other\InvoiceCorrectionType;
use App\Models\Other\SaleInvoice\FilterOption;
use App\Modules\SaleInvoice\Services\Directors\Creator;
use App\Modules\SaleInvoice\Services\Directors\Updater;
use App\Modules\SaleInvoice\Services\Factory\Builder;
use Carbon\Carbon;
use Illuminate\Database\Connection;
use App\Models\Db\User;
use App\Models\Db\InvoiceRegistry;
use App\Models\Other\InvoiceTypeStatus;
use Illuminate\Support\Collection;

class Invoice
{
    /**
     * @var InvoiceItem
     */
    protected $invoice_item;

    /**
     * @var ModelInvoice
     */
    protected $invoice;

    /**
     * @var Connection
     */
    protected $db;

    /**
     * @var InvoiceRegistry
     */
    protected $invoice_registry;

    /**
     * @var Creator
     */
    protected $director;

    /**
     * @var Updater
     */
    protected $update_director;

    /**
     * @var Builder
     */
    protected $factory_builder;

    /**
     * @var BaseDocument
     */
    protected $base_document;

    /**
     * @var Cashflow
     */
    protected $cashflow;

    /**
     * @var Payment
     */
    protected $payment;

    /**
     * @var Billing
     */
    protected $billing;

    /**
     * @var ServiceUnit
     */
    protected $service_unit;

    /**
     * CreateInvoice constructor.
     *
     * @param Connection $db
     * @param ModelInvoice $invoice
     * @param InvoiceItem $invoice_item
     * @param InvoiceRegistry $invoice_registry
     * @param Creator $director
     * @param Builder $factory_builder
     * @param BaseDocument $base_document
     * @param Updater $update_director
     * @param Cashflow $cashflow
     * @param Payment $payment
     * @param Billing
     */
    public function __construct(
        Connection $db,
        ModelInvoice $invoice,
        InvoiceItem $invoice_item,
        InvoiceRegistry $invoice_registry,
        Creator $director,
        Builder $factory_builder,
        BaseDocument $base_document,
        Updater $update_director,
        Cashflow $cashflow,
        Payment $payment,
        Billing $billing,
        ServiceUnit $service_unit
    ) {
        $this->db = $db;
        $this->invoice_item = $invoice_item;
        $this->invoice = $invoice;
        $this->invoice_registry = $invoice_registry;
        $this->director = $director;
        $this->factory_builder = $factory_builder;
        $this->base_document = $base_document;
        $this->update_director = $update_director;
        $this->cashflow = $cashflow;
        $this->payment = $payment;
        $this->billing = $billing;
        $this->service_unit = $service_unit;
    }

    /**
     * Check that company services same as stored in database.
     *
     * @param Request $request
     *
     * @return bool
     */
    public function companyServicesSameAsItems(Request $request)
    {
        foreach ($request->input('items') as $item) {
            if (isset($item['position_corrected_id'])) {
                $invoice_item = $this->invoice_item->where('id', $item['position_corrected_id'])
                    ->where('company_service_id', $item['company_service_id'])
                    ->first();
                if (empty($invoice_item)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Create CreateInvoice.
     *
     * @param Request $request
     * @param InvoiceRegistry $registry
     * @param User $user
     *
     * @return mixed
     */
    public function create(Request $request, InvoiceRegistry $registry, User $user)
    {
        $this->director->incomingParams($request, $registry, $user);

        return $this->db->transaction(function () use ($request) {
            return $this->director->build(
                $this->factory_builder->create(
                    $request->input('invoice_type_id')
                )
            );
        });
    }

    /**
     * Update CreateInvoice.
     *
     * @param Request $request
     * @param ModelInvoice $invoice
     * @param User $user
     *
     * @return mixed
     */
    public function update(Request $request, ModelInvoice $invoice, User $user)
    {
        $this->update_director->incomingParams($request, $invoice, $user);

        return $this->db->transaction(function () use ($invoice) {
            return $this->update_director->build(
                $this->factory_builder->create(
                    $invoice->invoiceType->id
                )
            );
        });
    }

    /**
     * Get invoice register or false if register not found.
     *
     * @param User $user
     * @param Request $request
     *
     * @return InvoiceRegistry|bool
     */
    public function getInvoiceRegistry(User $user, Request $request)
    {
        $invoice_registry = $this->invoice_registry->inCompany($user)
            ->findOrFail($request->input('invoice_registry_id'));

        return $invoice_registry;
    }

    public function getCompanyInvoicesWithFilters($request, $auth, $remove_deleted = false)
    {
        $invoices_query =
            ModelInvoice::withTrashed()->with('nodeInvoices', 'parentInvoices', 'receipts', 'onlineSales')
                ->inCompany($auth->user());

        $status = $request->input('status');
        $date_start_input = $request->input('date_start');
        $date_end_input = $request->input('date_end');
        $contractor = $request->input('contractor');

        if ($status == FilterOption::PAID) {
            $invoices_query->whereNotNull('paid_at');
        } elseif ($status == FilterOption::NOT_PAID) {
            $invoices_query->whereNull('paid_at')->whereHas('invoiceType', function ($q) {
                $q->where('slug', '!=', InvoiceTypeStatus::PROFORMA);
            });
        } elseif ($status == FilterOption::PAID_LATE) {
            $invoices_query->paidLate();
        } elseif ($status == FilterOption::DELETED) {
            $invoices_query->whereNotNull('deleted_at');
        }

        if (($remove_deleted && $status != FilterOption::DELETED) || $status == FilterOption::NOT_DELETED) {
            $invoices_query->whereNull('deleted_at');
        }

        if ($date_start_input) {
            $invoices_query->whereDate('issue_date', '>=', $date_start_input);
        }

        if ($date_end_input) {
            $invoices_query->whereDate('issue_date', '<=', $date_end_input);
        }

        $invoices_query->with([
            'drawer' => function ($query) {
                $query->select([
                    'id',
                    'first_name',
                    'last_name',
                ]);
            },
        ]);

        $invoices_query->with('invoiceContractor');

        if ($contractor) {
            $invoices_query->whereHas('invoiceContractor', function ($query) use ($contractor) {
                $query->where('vatin', 'like', '%' . $contractor . '%')
                    ->orWhere('name', 'like', '%' . $contractor . '%');
            });
        }

        return $invoices_query;
    }

    public function duplicateInvoiceForSingleSaleDocument(Request $request, User $user)
    {
        if ($this->base_document->creatingFromBaseDocument($request->input('extra_item_type'))) {
            $invoices_handle_document = $this->invoice->select('invoices.id')->inCompany($user);

            if ($request->input('extra_item_type') == 'receipts') {
                return $invoices_handle_document
                        ->whereHas('receipts', function ($query) use ($request) {
                            $query->where('id', $request->input('extra_item_id'));
                        })->first() !== null;
            } else {
                return $invoices_handle_document
                        ->whereHas('onlineSales', function ($query) use ($request) {
                            $query->where('id', $request->input('extra_item_id'));
                        })->first() !== null;
            }
        }

        return false;
    }

    /**
     * Add payment for invoice.
     *
     * @param Request $request
     * @param User $user
     *
     * @return InvoicePayment
     */
    public function addPayment(Request $request, User $user)
    {
        return $this->db->transaction(function () use ($request, $user) {
            $amount = normalize_price($request->input('amount'));
            $invoice = $this->invoice::findOrFail($request->input('invoice_id'));
            $payment_method = $this->payment->getMethod($request->input('payment_method_id'));
            $invoice_payment = $this->payment->add($invoice, $user, $payment_method, $amount);

            if ($this->payment->paymentInAdvance($payment_method->id)) {
                $this->cashflow->in($invoice, $user, $payment_method, $amount);
            }

            if ($this->billing->completed($invoice)) {
                $invoice->paid_at = Carbon::now();
                $invoice->payment_left = 0;
                $invoice->save();
            } else {
                $invoice->payment_left = $this->billing->countPaymentLeft($invoice);
                $invoice->save();
            }

            $this->billing->countPaymentLeft($invoice);

            return $invoice_payment;
        });
    }

    /**
     * Delete invoice payment.
     *
     * @param int $invoice_payment_id
     * @param User $user
     */
    public function deletePayment($invoice_payment_id, User $user)
    {
        $invoice_payment = InvoicePayment::whereHas('invoice', function ($q) use ($user) {
            $q->inCompany($user);
        })->where('special_partial_payment', 0)->findOrFail($invoice_payment_id);

        $this->db->transaction(function () use ($invoice_payment, $user) {
            $invoice_payment->delete();

            $invoice = $this->invoice->find($invoice_payment->invoice_id);

            if ($invoice) {
                $invoice_payment_left = $this->billing->countPaymentLeft($invoice);

                if ($invoice_payment_left <= 0) {
                    $invoice->paid_at = Carbon::now();
                    $invoice->payment_left = 0;
                    $invoice->save();
                } else {
                    $invoice->paid_at = null;
                    $invoice->payment_left = $invoice_payment_left;
                    $invoice->save();
                }
            }
        });
    }

    /**
     * Get invoice correction types.
     *
     * @param Request $request
     * @param User $user
     * @return Collection|array
     */
    public function allowCorrectionTypes(Request $request, User $user)
    {
        $invoice_id = $request->input('invoice_id');
        $correction_types = collect(InvoiceCorrectionType::all($user->selectedCompany()))->map(function ($description, $slug) {
            return [
               'slug' => $slug,
               'description' => $description,
           ];
        });
        if (empty($invoice_id)) {
            return $correction_types->values();
        }
        $invoice = $this->invoice->find($invoice_id);
        switch ($invoice->invoiceType->slug) {
            case InvoiceTypeStatus::PROFORMA:
                return [];
            case InvoiceTypeStatus::MARGIN:
            case InvoiceTypeStatus::MARGIN_CORRECTION:
            case InvoiceTypeStatus::REVERSE_CHARGE:
            case InvoiceTypeStatus::REVERSE_CHARGE_CORRECTION:
            return collect($correction_types)->filter(function ($type, $key) {
                return $key != InvoiceCorrectionType::TAX;
            })->values();
            default:
                return $correction_types->values();
        }
    }

    /**
     * Check allowing updating invoice depend on application settings.
     *
     * @param $attribute
     * @param $value
     * @param $parameters
     */
    public function validateAllowUpdatingInvoice($attribute, $value, $parameters)
    {
        return $this->invoice->where('id', $value)
            ->whereIn('invoice_type_id', $parameters)
            ->count() > 0;
    }

    /**
     * Checks if passed quantity can be decimal.
     *
     * @param $attribute
     * @param $value
     * @param $parameters
     *
     * @return bool
     */
    public function validateDecimalQuantity($attribute, $value, $parameters)
    {
        $service_unit = $this->service_unit->find($parameters[0]);
        if ($service_unit &&
            ! $service_unit->decimal &&
            is_float($value)) {
            return false;
        }

        return true;
    }

    /**
     * Validate if Invoice can be edit.
     *
     * @param $attribute
     * @param $value
     * @param $parameters
     *
     * @return bool
     */
    public function validateIsEditableInvoice($attribute, $value, $parameters)
    {
        $invoice = $this->invoice->findOrFail($value);

        return $invoice->isEditable();
    }

    /**
     * Check invoice has parent in store method.
     *
     * @param $request
     * @return bool
     */
    public function checkHasParent($request)
    {
        $corrected_invoice_id = $request->input('corrected_invoice_id');
        if ($corrected_invoice_id && $invoice = ModelInvoice::find($corrected_invoice_id)) {
            if ($invoice->parentInvoices()->count()) {
                return true;
            }
        }

        return false;
    }
}
