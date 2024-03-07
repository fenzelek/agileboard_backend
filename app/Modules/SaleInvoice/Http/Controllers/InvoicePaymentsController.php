<?php

namespace App\Modules\SaleInvoice\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Modules\SaleInvoice\Http\Requests\InvoicePayments as RequestInvoicePayments;
use App\Models\Db\InvoicePayment;
use App\Modules\SaleInvoice\Http\Requests\StoreInvoicePayment as RequestStoreInvoicePayment;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Response;
use App\Modules\SaleInvoice\Services\Invoice as ServiceInvoice;

class InvoicePaymentsController extends Controller
{
    /**
     * Display a listing of invoice payments.
     *
     * @param RequestInvoicePayments $request
     *
     * @return Response
     */
    public function index(RequestInvoicePayments $request)
    {
        return ApiResponse::responseOk(InvoicePayment::where(
            'invoice_id',
            $request->input('invoice_id')
        )->orderBy('id')->get());
    }

    /**
     * Add new invoice payment.
     *
     * @param RequestStoreInvoicePayment $request
     * @param ServiceInvoice $service_invoice
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(RequestStoreInvoicePayment $request, ServiceInvoice $service_invoice)
    {
        $invoice_payment = $service_invoice->addPayment($request, auth()->user());

        return ApiResponse::responseOk($invoice_payment, 201);
    }

    public function destroy($id, Guard $auth, ServiceInvoice $service_invoice)
    {
        $service_invoice->deletePayment($id, $auth->user());

        return ApiResponse::responseOk([], 204);
    }
}
