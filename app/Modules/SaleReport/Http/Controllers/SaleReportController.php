<?php

namespace App\Modules\SaleReport\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Helpers\ErrorCode;
use App\Http\Controllers\Controller;
use App\Models\Other\ModuleType;
use App\Modules\SaleOther\Http\Requests\Receipt as RequestReceipt;
use App\Modules\SaleOther\Http\Requests\OnlineSale as RequestOnlineSale;
use App\Modules\SaleReport\Services\ExternalReport;
use App\Modules\SaleReport\Services\Report as ServiceReport;
use App\Services\Paginator;
use App\Modules\SaleReport\Http\Requests\InvoicesRegistry;
use App\Models\Db\Invoice as ModelInvoice;
use App\Http\Resources\RegisterOfInvoices as TransformerRegisterOfInvoices;
use App\Models\Db\InvoiceTaxReport as ModelInvoiceTaxReport;
use App\Http\Resources\InvoiceTaxReportIncludeName as TransformerInvoiceTaxReportIncludeName;
use App\Models\Db\Company as ModelCompany;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use PDF;

class SaleReportController extends Controller
{
    public function reportReceipts(RequestReceipt $request, ServiceReport $service)
    {
        $report = $service->receiptsReport($request, auth()->user());

        return ApiResponse::responseOk($report);
    }

    public function reportOnlineSales(RequestOnlineSale $request, ServiceReport $service)
    {
        $report = $service->onlineSalesReport($request, auth()->user());

        return ApiResponse::responseOk($report);
    }

    public function invoicesRegistry(InvoicesRegistry $request, ServiceReport $service, Paginator $paginator)
    {
        $invoices_query = $service->filterInvoicesRegistry($request, auth()->user());

        $invoices = $paginator->get($invoices_query->orderBy('sale_date', 'asc')->orderBy('id'), 'reports.invoice-registry');

        return ApiResponse::transResponseOk($invoices, 200, [
            ModelInvoice::class => TransformerRegisterOfInvoices::class,
            ModelInvoiceTaxReport::class => TransformerInvoiceTaxReportIncludeName::class,
        ]);
    }

    public function reportInvoicesRegistry(InvoicesRegistry $request, ServiceReport $service)
    {
        $report = $service->invoicesRegisterReport($request, auth()->user());

        return ApiResponse::responseOk($report);
    }

    public function invoicesRegistryPdf(InvoicesRegistry $request, ServiceReport $service)
    {
        $invoices = $service->filterInvoicesRegistry($request, auth()->user())
            ->orderBy('sale_date', 'asc')
            ->orderBy('id')
            ->get();

        $report = $service->invoicesRegisterReport($request, auth()->user());

        $pdf = PDF::loadView('pdf.invoicesRegistry', [
            'company' => ModelCompany::find(auth()->user()->getSelectedCompanyId()),
            'invoices' => $invoices,
            'report' => $report,
            'year' => $request->input('year'),
            'month' => $request->input('month'),
        ]);

        return $pdf->stream('invoicesRegistry-' . Carbon::now()->format('Y-m-d    ') . '.pdf');
    }

    public function invoicesRegistryXls(InvoicesRegistry $request, ServiceReport $service)
    {
        return $service->invoicesRegistryExport($request, auth()->user());
    }

    /**
     * Export invoices registry to format based on company settings.
     *
     * @param InvoicesRegistry $request
     * @param ExternalReport $service
     *
     * @return JsonResponse|\Illuminate\Http\Response
     * @throws \Throwable
     */
    public function invoicesRegisterExport(InvoicesRegistry $request, ExternalReport $service)
    {
        $user = auth()->user();
        $export_provider_name = $user->selectedCompany()
            ->appSettings(ModuleType::INVOICES_REGISTER_EXPORT_NAME);

        if ($export_provider_name == '') {
            return ApiResponse::responseError(ErrorCode::PACKAGE_CANT_USE_CUSTOM_EXPORTS, 426);
        }

        $provider = $service->getProvider($export_provider_name);
        $file_content = $provider->getFileContent($service->getInvoices($request, $user));

        $headers = [
            'Content-type' => $provider->getFileContentType(),
            'Content-Disposition' => 'attachment; filename=' . $provider->getFileName(),
        ];

        return response()->make($file_content, 200, $headers);
    }
}
