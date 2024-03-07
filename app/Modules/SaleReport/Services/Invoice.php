<?php

namespace App\Modules\SaleReport\Services;

use App\Models\Db\Invoice as ModelInvoice;
use App\Models\Other\SaleInvoice\FilterOption;

class Invoice
{
    /**
     * @var ModelInvoice
     */
    protected $invoice;

    /**
     * Receipt constructor.
     *
     * @param ModelInvoice $invoice
     */
    public function __construct(
        ModelInvoice $invoice
    ) {
        $this->invoice = $invoice;
    }

    public function getCompanyInvoicesWithFilters($request, $auth)
    {
        $invoices_query = $this->invoice->inCompany($auth->user());

        $status = $request->input('status');
        $date_start_input = $request->input('date_start');
        $date_end_input = $request->input('date_end');
        $contractor = $request->input('contractor');

        if ($status == FilterOption::PAID) {
            $invoices_query->whereNotNull('paid_at');
        } elseif ($status == FilterOption::NOT_PAID) {
            $invoices_query->whereNull('paid_at');
        } elseif ($status == FilterOption::PAID_LATE) {
            $invoices_query->paidLate();
        } elseif ($status == FilterOption::DELETED) {
            $invoices_query->whereNotNull('deleted_at');
        } elseif ($status == FilterOption::NOT_DELETED) {
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

    public function filterCompanyInvoicesReportSummary($invoice_report_query)
    {
        $price_net_sum = $invoice_report_query->sum('price_net');
        $price_gross_sum = $invoice_report_query->sum('price_gross');
        $vat_sum_sum = $invoice_report_query->sum('vat_sum');
        $payment_left_sum = $invoice_report_query->sum('payment_left');

        $result = [
            'price_net_sum' => $this->undoNormalizeAmount($price_net_sum),
            'price_gross_sum' => $this->undoNormalizeAmount($price_gross_sum),
            'vat_sum_sum' => $this->undoNormalizeAmount($vat_sum_sum),
            'payment_left_sum' => $this->undoNormalizeAmount($payment_left_sum),
        ];

        return $result;
    }

    public function undoNormalizeAmount($amount)
    {
        return denormalize_price($amount);
    }
}
