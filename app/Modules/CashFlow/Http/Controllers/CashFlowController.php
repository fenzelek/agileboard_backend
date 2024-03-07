<?php

namespace App\Modules\CashFlow\Http\Controllers;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Models\Db\CashFlow;
use App\Models\Db\Company;
use App\Models\Db\User;
use App\Modules\CashFlow\Services\CashFlow as ServiceCashFlow;
use App\Modules\CashFlow\Http\Requests\CashFlow as CashFlowRequest;
use App\Modules\CashFlow\Http\Requests\CashFlowStore as RequestCashFlowStore;
use App\Modules\CashFlow\Http\Requests\CashFlowUpdate as RequestCashFlowUpdate;
use Illuminate\Contracts\Auth\Guard;
use App\Services\Paginator;
use PDF;
use App\Models\Db\Invoice;
use App\Http\Resources\ShortInvoice;

class CashFlowController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param CashFlowRequest $request
     * @param Paginator $paginator
     * @param ServiceCashFlow $service
     *
     * @return Response
     */
    public function index(CashFlowRequest $request, Paginator $paginator, ServiceCashFlow $service)
    {
        $balanced = $request->input('balanced', 0);
        if ($balanced == 1) {
            $paginated = $service->getBalanced($request, auth()->user());
            $cash_flows = $paginator->decorate($paginated, 'cash-flow.index');
        } else {
            $cash_flow_query = $service->filterCashFlow($request, auth()->user());
            $cash_flows = $paginator->get($cash_flow_query->with('receipt', 'invoice')
                ->orderBy('id'), 'cash-flow.index');
        }

        return ApiResponse::transResponseOk($cash_flows, 200, [
            Invoice::class => ShortInvoice::class,
        ]);
    }

    /**
     * Store new cash flow.
     *
     * @param RequestCashFlowStore $request
     * @param Guard $auth
     *
     * @return Response
     */
    public function store(RequestCashFlowStore $request, Guard $auth)
    {
        $cash_flow = CashFlow::create([
            'company_id' => $auth->user()->getSelectedCompanyId(),
            'user_id' => $auth->user()->id,
            $request->input('document_type') . '_id' => $request->input('document_id'),
            'amount' => normalize_price($request->input('amount')),
            'direction' => $request->input('direction'),
            'description' => $request->input('description'),
            'flow_date' => $request->input('flow_date'),
            'cashless' => $request->input('cashless'),
        ]);

        return ApiResponse::responseOk($cash_flow, 201);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param RequestCashFlowUpdate $request
     * @param int $id
     * @param Guard $auth
     *
     * @return ApiResponse
     */
    public function update(RequestCashFlowUpdate $request, $id, Guard $auth)
    {
        $cash_flow = CashFlow::inCompany($auth->user())
            ->where('user_id', auth()->user()->id)
            ->findOrFail($id);
        $cash_flow->cashless = $request->input('cashless');
        $cash_flow->save();

        return ApiResponse::responseOk([], 200);
    }

    public function pdf(CashFlowRequest $request, ServiceCashFlow $service)
    {
        $params = [];
        if ($request->input('date')) {
            $params['date'] = $request->input('date');
        }
        if ($request->input('user_id')) {
            $params['user'] = User::findOrFail($request->input('user_id'));
        }

        $balanced = $request->input('balanced', 0);
        if ($balanced == 1) {
            $cash_flows = $service->getBalanced($request, auth()->user(), false);
        } else {
            $cash_flows = $service->filterCashFlow($request, auth()->user())
                ->with('receipt', 'invoice')->orderBy('id')->get();
        }

        $data = [
            'cash_flows' => $cash_flows,
            'company' => Company::findOrFail(auth()->user()->getSelectedCompanyId()),
            'report' => $service->filterCashFlowReportSummary($request, auth()->user()),
            'params' => $params,
            'cashless' => $request->input('cashless'),
            'balanced' => $balanced,
        ];

        $pdf = PDF::loadView('pdf.cash-flows', $data);

        return $pdf->stream('lista-operacji-kasowych-' .
            str_slug(Carbon::now()->toDateTimeString()) . '.pdf');
    }

    public function pdfItem($id, Guard $auth)
    {
        $cash_flow = CashFlow::inCompany($auth->user())->findOrFail($id);

        $data = [
            'cash_flow' => $cash_flow,
            'company' => Company::findOrFail(auth()->user()->getSelectedCompanyId()),
        ];

        $pdf = PDF::loadView('pdf.cash-flow-item', $data);

        return $pdf->stream('operacja-kasowa-' . $cash_flow->id . '-' .
            str_slug(Carbon::now()->toDateTimeString()) . '.pdf');
    }
}
