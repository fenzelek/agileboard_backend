<?php

namespace App\Modules\SaleInvoice\Http\Controllers;

use App\Filters\InvoiceFilter;
use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Models\Db\EmailLog;
use App\Models\Other\ModuleType;
use App\Modules\SaleInvoice\Http\Requests\DestroyInvoice;
use App\Modules\SaleInvoice\Http\Requests\SendInvoice;
use App\Modules\SaleInvoice\Jobs\CreateInvoicesPackage;
use App\Services\Paginator;
use Illuminate\Contracts\Auth\Guard;
use App\Helpers\ErrorCode;
use App\Modules\SaleInvoice\Http\Requests\CreateInvoice;
use App\Modules\SaleInvoice\Http\Requests\CreateInvoicePdf;
use App\Modules\SaleInvoice\Services\Invoice as ServiceInvoice;
use App\Models\Db\Invoice as ModelInvoice;
use Carbon\Carbon;
use DB;
use PDF;
use App\Modules\SaleInvoice\Http\Requests\CompanyInvoices as RequestCompanyInvoices;
use App\Modules\SaleInvoice\Http\Requests\UpdateInvoice as RequestUpdateInvoice;
use App\Jobs\SendInvoice as SendJob;

class InvoiceController extends Controller
{
    /**
     * List invoices.
     *
     * @param RequestCompanyInvoices $request
     * @param Guard $auth
     * @param Paginator $paginator
     * @param ServiceInvoice $service
     * @param InvoiceFilter $filter
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(
        RequestCompanyInvoices $request,
        Guard $auth,
        Paginator $paginator,
        ServiceInvoice $service,
        InvoiceFilter $filter
    ) {
        $company_invoices = $service->getCompanyInvoicesWithFilters($request, $auth);
        $invoices = $paginator->get($company_invoices->filtered($filter)->orderBy('id'), 'invoices.index');

        return ApiResponse::responseOk($invoices);
    }

    /**
     * Create invoice.
     *
     * @param CreateInvoice $request
     * @param ServiceInvoice $service
     * @param Guard $auth
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateInvoice $request, ServiceInvoice $service, Guard $auth)
    {
        $invoice_registry = $service->getInvoiceRegistry($auth->user(), $request);

        if (! $service->companyServicesSameAsItems($request)) {
            return ApiResponse::responseError(
                ErrorCode::INVOICE_PROTECT_CORRECTION_OF_COMPANY_SERVICE,
                421
            );
        }

        if ($service->duplicateInvoiceForSingleSaleDocument($request, $auth->user())) {
            return ApiResponse::responseError(
                ErrorCode::INVOICES_DUPLICATE_INVOICE_FOR_SINGLE_SALE_DOCUMENT,
                423
            );
        }

        if ($service->checkHasParent($request)) {
            return ApiResponse::responseError(ErrorCode::INVOICE_HAS_CORRECTION, 424);
        }

        $invoice = $service->create($request, $invoice_registry, auth()->user());

        return ApiResponse::responseOk($invoice, 201);
    }

    /**
     * Show invoice.
     *
     * @param $id
     * @param Guard $auth
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id, Guard $auth)
    {
        $invoice = ModelInvoice::inCompany($auth->user())
            ->with([
                'items.serviceUnit',
                'taxes',
                'finalAdvanceTaxes',
                'invoiceCompany' => function ($query) {
                    $query->with(['vatinPrefix']);
                },
                'invoiceContractor' => function ($query) {
                    $query->with(['vatinPrefix']);
                },
                'invoiceDeliveryAddress',
                'deliveryAddress',
                'nodeInvoices',
                'parentInvoices',
                'onlineSales' => function ($query) {
                    $query->select(['id', 'number']);
                },
                'receipts' => function ($query) {
                    $query->select(['id', 'number']);
                },
                'specialPayments',
                'bankAccount',
            ])->findOrFail($id);

        return ApiResponse::responseOk($invoice, 200);
    }

    /**
     * Update invoice.
     *
     * @param RequestUpdateInvoice $request
     * @param ServiceInvoice $service
     * @param $id
     * @param Guard $auth
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(RequestUpdateInvoice $request, ServiceInvoice $service, $id, Guard $auth)
    {
        $invoice = ModelInvoice::inCompany($auth->user())->findOrFail($id);

        $invoice = $service->update($request, $invoice, auth()->user());

        return ApiResponse::responseOK($invoice, 200);
    }

    /**
     * Create pdf.
     *
     * @param CreateInvoicePdf $request
     * @param $id
     * @param Guard $auth
     * @return \Illuminate\Http\JsonResponse
     */
    public function pdf(CreateInvoicePdf $request, $id, Guard $auth)
    {
        $invoice = ModelInvoice::with('company', 'items.positionCorrected.serviceUnit', 'items.serviceUnit')
            ->inCompany($auth->user())->findOrFail($id);

        if ($request->input('duplicate') && $invoice->isProforma()) {
            return ApiResponse::responseError(
                ErrorCode::INVOICE_DUPLICATE_FOR_PROFORMA_IS_NOT_ALLOWED,
                424
            );
        }
        $invoice->last_printed_at = Carbon::now();
        $invoice->save();

        $pdf = PDF::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'duplicate' => $request->input('duplicate'),
            'bank_info' => $invoice->paymentMethod->paymentPostponed(),
            'footer' => $auth->user()->selectedCompany()
                ->appSettings(ModuleType::INVOICES_FOOTER_ENABLED),
        ]);

        return $pdf->stream('faktura-' . str_slug($invoice->number) . '.pdf');
    }

    /**
     * Soft delete invoice and cash flows.
     *
     * @param DestroyInvoice $request
     * @param ServiceInvoice $service
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(DestroyInvoice $request)
    {
        $invoice = ModelInvoice::findOrFail($request->id);

        if ($invoice->parentInvoices()->count()) {
            return ApiResponse::responseError(ErrorCode::INVOICE_HAS_CORRECTION, 424);
        }

        DB::transaction(function () use ($invoice) {
            $invoice->cashFlows()->delete();
            $invoice->delete();
        });

        return ApiResponse::responseOk([], 204);
    }

    /**
     * Send pdf invoice to email.
     *
     * @param SendInvoice $request
     * @param EmailLog $email_log_model
     * @param $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function send(SendInvoice $request, EmailLog $email_log_model, $id)
    {
        $invoice = ModelInvoice::findOrFail($id);

        $email_log = $email_log_model->create([
            'email' => $request->input('email'),
            'title' => 'Invoice',
            'user_id' => auth()->id(),
            'invoice_id' => $invoice->id,
        ]);
        $job_number = $this->dispatch(new SendJob($email_log));

        return ApiResponse::responseOk([]);
    }

    /**
     * Generate PDF with list of invoices.
     *
     * @param RequestCompanyInvoices $request
     * @param Guard $auth
     * @param ServiceInvoice $service
     *
     * @return mixed
     */
    public function indexPdf(
        RequestCompanyInvoices $request,
        Guard $auth,
        ServiceInvoice $service,
        InvoiceFilter $filter
    ) {
        $company_invoices = $service->getCompanyInvoicesWithFilters($request, $auth, true)
            ->filtered($filter)->orderBy('id')->get();

        $pdf = PDF::loadView('pdf.invoices-listing', [
            'invoices' => $company_invoices,
            'request' => $request,
            'footer' => $auth->user()->selectedCompany()
                ->appSettings(ModuleType::INVOICES_FOOTER_ENABLED),
        ]);

        return $pdf->stream('lista-faktur.pdf');
    }

    /**
     * @param RequestCompanyInvoices $request
     * @param Guard $auth
     * @param ServiceInvoice $service
     * @param InvoiceFilter $filter
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexZip(
        RequestCompanyInvoices $request,
        Guard $auth,
        ServiceInvoice $service,
        InvoiceFilter $filter
    ) {
        $company_invoices = $service->getCompanyInvoicesWithFilters($request, $auth, true)
            ->filtered($filter)->orderBy('id')->get();

        if ($company_invoices->count() > (int) (config('invoices.package_overflow'))) {
            return ApiResponse::responseError(
                ErrorCode::INVOICE_PACKAGE_BUFFER_OVERFLOW,
                427
            );
        }

        dispatch(new CreateInvoicesPackage($company_invoices));

        return ApiResponse::responseOk(['count' => $company_invoices->count()]);
    }
}
