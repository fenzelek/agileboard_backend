<?php

namespace App\Modules\SaleReport\Services;

use Illuminate\Http\Request;
use App\Models\Db\InvoiceType;
use App\Models\Db\User;
use App\Models\Other\DatePeriod;
use App\Models\Other\InvoiceTypeStatus;
use App\Modules\SaleOther\Services\Receipt as ServiceReceipt;
use App\Modules\SaleOther\Services\OnlineSale as ServiceOnlineSale;
use App\Models\Db\Receipt as ModelReceipt;
use App\Models\Db\OnlineSale as ModelOnlineSale;
use App\Models\Db\Invoice as ModelInvoice;
use Carbon\Carbon;
//use App\Helpers\ExcelExport;
use Excel;
use App\Exports\InvoicesExport;

class Report
{
    /**
     * @var ModelReceipt
     */
    protected $receipt;

    /**
     * @var ServiceReceipt
     */
    protected $service_receipt;

    /**
     * @var ModelOnlineSale
     */
    protected $online_sale;

    /**
     * @var ServiceOnlineSale
     */
    protected $service_online_sale;

    /**
     * @var ModelInvoice
     */
    protected $invoice;

    /**
     * @var InvoiceType;
     */
    protected $invoice_type;

    /**
     * Report constructor.
     *
     * @param ModelReceipt $receipt
     * @param ServiceReceipt $service_receipt
     * @param ModelOnlineSale $online_sale
     * @param ServiceOnlineSale $service_online_sale
     * @param ModelInvoice $invoice
     * @param InvoiceType $invoice_type
     */
    public function __construct(
        ModelReceipt $receipt,
        ServiceReceipt $service_receipt,
        ModelOnlineSale $online_sale,
        ServiceOnlineSale $service_online_sale,
        ModelInvoice $invoice,
        InvoiceType $invoice_type
    ) {
        $this->receipt = $receipt;
        $this->service_receipt = $service_receipt;
        $this->online_sale = $online_sale;
        $this->service_online_sale = $service_online_sale;
        $this->invoice = $invoice;
        $this->invoice_type = $invoice_type;
    }

    /**
     * Calculate data for receipts report.
     *
     * @param Request $request
     * @param User $user
     *
     * @return array
     */
    public function receiptsReport(Request $request, User $user)
    {
        $sums = $this->service_receipt->filterReceipt($request, $user)
            ->selectRaw('SUM(price_net) as price_net, SUM(price_gross) AS price_gross, SUM(vat_sum) AS vat_sum')
            ->first();

        return [
            'price_net_report' => (float) $this->receipt->undoNormalizeAmount($sums->price_net),
            'price_gross_report' => (float) $this->receipt->undoNormalizeAmount($sums->price_gross),
            'vat_sum_report' => (float) $this->receipt->undoNormalizeAmount($sums->vat_sum),
        ];
    }

    /**
     * Calculate data for online sales report.
     *
     * @param Request $request
     * @param User $user
     *
     * @return array
     */
    public function onlineSalesReport(Request $request, User $user)
    {
        $sums = $this->service_online_sale->filterOnlineSale($request, $user)
            ->selectRaw('SUM(price_net) as price_net, SUM(price_gross) AS price_gross, SUM(vat_sum) AS vat_sum')
            ->first();

        return [
            'price_net_report' => (float) $this->receipt->undoNormalizeAmount($sums->price_net),
            'price_gross_report' => (float) $this->receipt->undoNormalizeAmount($sums->price_gross),
            'vat_sum_report' => (float) $this->receipt->undoNormalizeAmount($sums->vat_sum),
        ];
    }

    /**
     * Filter Invoice for prepare invoice registry.
     *
     * @param Request $request
     * @param User $user
     *
     * @return mixed
     */
    public function filterInvoicesRegistry(Request $request, User $user)
    {
        $invoices_query = $this->invoice->inCompany($user);

        if ($request->input('invoice_type_id')) {
            $invoices_query->where('invoice_type_id', $request->input('invoice_type_id'));
        } else {
            $invoices_query->where('invoice_type_id', '!=', $this->invoice_type::findBySlug(InvoiceTypeStatus::PROFORMA)->id);
        }

        if ($request->input('year')) {
            $period = app()->make(DatePeriod::class);
            if ($request->input('month')) {
                $period->start = Carbon::create($request->input('year'), $request->input('month'))->firstOfMonth();
                $period->end = Carbon::create($request->input('year'), $request->input('month'))->endOfMonth();
            } else {
                $period->start = Carbon::create($request->input('year'))->firstOfYear();
                $period->end = Carbon::create($request->input('year'))->endOfYear();
            }
            $invoices_query->filterBySaleDate($period);
        }

        if ($request->input('vat_rate_id')) {
            $invoices_query->with(['invoiceContractor', 'taxes' => function ($query) use ($request) {
                $query->where('vat_rate_id', $request->input('vat_rate_id'));
            }])->whereHas('taxes', function ($query) use ($request) {
                $query->where('vat_rate_id', $request->input('vat_rate_id'));
            });
        } else {
            $invoices_query->with(['invoiceContractor', 'taxes']);
        }

        return $invoices_query;
    }

    public function invoicesRegisterReport(Request $request, User $user)
    {
        $invoices = $this->filterInvoicesRegistry($request, $user)->orderBy('id')->get();
        $taxes = [];
        foreach ($invoices as $invoice) {
            foreach ($invoice->taxes as $tax) {
                if (array_key_exists($tax->vat_rate_id, $taxes)) {
                    $taxes[$tax->vat_rate_id]['price_net'] += $tax->price_net;
                    $taxes[$tax->vat_rate_id]['price_gross'] += $tax->price_gross;
                } else {
                    $taxes[$tax->vat_rate_id] = [
                        'vat_rate_id' => $tax->vat_rate_id,
                        'vat_rate_name' => $tax->vatRate->name,
                        'price_net' => $tax->price_net,
                        'price_gross' => $tax->price_gross,
                    ];
                }
            }
        }
        $summary = [
            'price_net' => 0,
            'vat_sum' => 0,
            'price_gross' => 0,
        ];
        foreach ($taxes as $key => $tax) {
            $vat = $tax['price_gross'] - $tax['price_net'];
            $summary['price_net'] += $taxes[$key]['price_net'];
            $summary['vat_sum'] += $vat;
            $summary['price_gross'] += $taxes[$key]['price_gross'];

            $taxes[$key]['vat_sum'] = (float) denormalize_price($vat);
            $taxes[$key]['price_net'] = (float) denormalize_price($tax['price_net']);
            $taxes[$key]['price_gross'] = (float) denormalize_price($tax['price_gross']);
        }

        $summary['price_net'] = (float) denormalize_price($summary['price_net']);
        $summary['vat_sum'] = (float) denormalize_price($summary['vat_sum']);
        $summary['price_gross'] = (float) denormalize_price($summary['price_gross']);

        return array_merge(['vat_rates' => array_values($taxes)], $summary);
    }

    public function invoicesRegistryExport(Request $request, User $user)
    {
        $invoices = $this->filterInvoicesRegistry($request, auth()->user())
            ->orderBy('sale_date', 'asc')
            ->orderBy('id')
            ->get();

        // Code below used for tests
        // Parameter `enable_test_xls` set in tests
        if (config('enable_test_xls', false)) {
            Excel::store(new InvoicesExport($invoices), 'data-export.xls');

            return null;
        }

        return Excel::download(new InvoicesExport($invoices), 'data-export.xls', [500]);
    }
}
