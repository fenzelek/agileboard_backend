<?php

namespace App\Modules\SaleOther\Http\Controllers;

use Carbon\Carbon;
use App\Helpers\ApiResponse;
use App\Helpers\ErrorCode;
use App\Http\Controllers\Controller;
use App\Models\Db\Company;
use App\Modules\SaleOther\Http\Requests\CreateReceipt;
use App\Modules\SaleOther\Http\Requests\Receipt as RequestReceipt;
use App\Modules\SaleOther\Services\Receipt as ServiceReceipt;
use App\Services\Paginator;
use App\Http\Resources\ShortInvoice as TransformerShortInvoice;
use App\Models\Db\Invoice as ModelInvoice;
use App\Models\Db\Receipt as ModelReceipt;
use Illuminate\Contracts\Auth\Guard;
use PDF;

class ReceiptController extends Controller
{
    public function store(CreateReceipt $request, ServiceReceipt $service)
    {
        $duplicate_transaction_number =
            $service->isTransactionNumberAlreadyUsed($request, auth()->user());

        if ($duplicate_transaction_number) {
            return ApiResponse::responseError(
                ErrorCode::OTHER_SALES_DUPLICATE_TRANSACTION_NUMBER,
                420
            );
        }

        $receipt = $service->create($request, auth()->user());

        return ApiResponse::responseOk($receipt, 201);
    }

    public function index(RequestReceipt $request, ServiceReceipt $service, Paginator $paginator)
    {
        $receipts_query = $service->filterReceipt($request, auth()->user());

        $receipts = $paginator->get(
            $receipts_query->with('invoices')->orderBy('id'),
            'receipts.index'
        );

        return ApiResponse::transResponseOk($receipts, 200, [
            ModelInvoice::class => TransformerShortInvoice::class,
        ]);
    }

    public function show($id, Guard $auth)
    {
        return ApiResponse::transResponseOk(
            ModelReceipt::with('items', 'invoices')
                ->inCompany($auth->user())->findOrFail($id),
            200,
            [ModelInvoice::class => TransformerShortInvoice::class]
        );
    }

    public function pdf(RequestReceipt $request, ServiceReceipt $service)
    {
        $receipts_query = $service->filterReceipt($request, auth()->user());

        $data = [
            'receipts' => $receipts_query->orderBy('id')->with('paymentMethod')->get(),
            'sum' => $receipts_query->sum('price_gross'),
            'company' => Company::findOrFail(auth()->user()->getSelectedCompanyId()),
            'params' => $service->getParams($request),
        ];

        $pdf = PDF::loadView('pdf.receipts', $data);

        return $pdf->stream('receipts-' . str_slug(Carbon::now()->toDateTimeString()) . '.pdf');
    }

    public function pdfReport(RequestReceipt $request, ServiceReceipt $service)
    {
        $receipt_items = $service->getReceiptItemsSummary($request);

        $pdf = PDF::loadView('pdf.receipts-report', [
            'receipt_items' => $receipt_items,
            'sum' => $receipt_items->sum('price_gross_sum'),
            'company' => Company::findOrFail(auth()->user()->getSelectedCompanyId()),
            'params' => $service->getParams($request),
        ]);

        return $pdf->stream('receipts-report-' . str_slug(Carbon::now()->toDateTimeString()) .
            '.pdf');
    }
}
