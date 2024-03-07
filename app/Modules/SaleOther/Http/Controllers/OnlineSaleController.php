<?php

namespace App\Modules\SaleOther\Http\Controllers;

use Carbon\Carbon;
use App\Helpers\ApiResponse;
use App\Helpers\ErrorCode;
use App\Http\Controllers\Controller;
use App\Models\Db\Company;
use App\Modules\SaleOther\Http\Requests\CreateOnlineSale;
use App\Modules\SaleOther\Http\Requests\OnlineSale as RequestOnlineSale;
use App\Modules\SaleOther\Services\OnlineSale as ServiceOnlineSale;
use App\Services\Paginator;
use App\Models\Db\Invoice as ModelInvoice;
use App\Http\Resources\ShortInvoice as TransformerShortInvoice;
use App\Models\Db\OnlineSale as ModelOnlineSale;
use Illuminate\Contracts\Auth\Guard;
use PDF;

class OnlineSaleController extends Controller
{
    public function store(CreateOnlineSale $request, ServiceOnlineSale $service)
    {
        $duplicate_transaction_number = $service->isTransactionNumberAlreadyUsed($request, auth()->user());

        if ($duplicate_transaction_number) {
            return ApiResponse::responseError(ErrorCode::OTHER_SALES_DUPLICATE_TRANSACTION_NUMBER, 420);
        }

        $online_sale = $service->create($request, auth()->user());

        return ApiResponse::responseOk($online_sale, 201);
    }

    public function index(RequestOnlineSale $request, ServiceOnlineSale $service, Paginator $paginator)
    {
        $online_sales_query = $service->filterOnlineSale($request, auth()->user());

        $online_sales = $paginator->get(
            $online_sales_query->with('invoices')->orderBy('id'),
            'onlineSale.index'
        );

        return ApiResponse::transResponseOk($online_sales, 200, [
            ModelInvoice::class => TransformerShortInvoice::class,
        ]);
    }

    public function show($id, Guard $auth)
    {
        return ApiResponse::transResponseOk(
            ModelOnlineSale::with('items', 'invoices')
                ->inCompany($auth->user())->findOrFail($id),
            200,
            [ModelInvoice::class => TransformerShortInvoice::class]
        );
    }

    /**
     * Generate PDF with online sales.
     *
     * @param RequestOnlineSale $request
     * @param ServiceOnlineSale $service
     *
     * @return mixed
     */
    public function pdf(RequestOnlineSale $request, ServiceOnlineSale $service)
    {
        $params = [];
        if ($request->input('date_start')) {
            $params['date_start'] = $request->input('date_start');
        }
        if ($request->input('date_end')) {
            $params['date_end'] = $request->input('date_end');
        }
        if ($request->input('transaction_number')) {
            $params['transaction_number'] = $request->input('transaction_number');
        }
        if ($request->input('number')) {
            $params['number'] = $request->input('number');
        }
        if ($request->input('email')) {
            $params['email'] = $request->input('email');
        }

        $online_sales_query = $service->filterOnlineSale($request, auth()->user());

        $data = [
            'online_sales' => $online_sales_query->orderBy('id')->get(),
            'sum' => $online_sales_query->sum('price_gross'),
            'company' => Company::findOrFail(auth()->user()->getSelectedCompanyId()),
            'params' => $params,
        ];

        $pdf = PDF::loadView('pdf.online-sales', $data);

        return $pdf->stream('online-sales-' . str_slug(Carbon::now()->toDateTimeString()) . '.pdf');
    }
}
