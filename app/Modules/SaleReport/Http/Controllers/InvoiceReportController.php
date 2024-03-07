<?php

namespace App\Modules\SaleReport\Http\Controllers;

use App\Filters\InvoiceReportFilter;
use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Modules\SaleReport\Http\Requests\CompanyInvoicesReport as RequestCompanyInvoices;
use App\Modules\SaleReport\Services\Invoice as ServiceInvoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Auth\Guard;

class InvoiceReportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param RequestCompanyInvoices $request
     * @param ServiceInvoice $service
     * @param Guard $auth
     * @param InvoiceReportFilter $invoice_filter
     *
     * @return JsonResponse
     */
    public function reportCompanyInvoices(
        RequestCompanyInvoices $request,
        ServiceInvoice $service,
        Guard $auth,
        InvoiceReportFilter $invoice_filter
    ) {
        $company_invoices_report =
            $service->getCompanyInvoicesWithFilters($request, $auth)->filtered($invoice_filter);
        $company_invoices_report_summary =
            $service->filterCompanyInvoicesReportSummary($company_invoices_report);

        return ApiResponse::responseOk($company_invoices_report_summary);
    }
}
