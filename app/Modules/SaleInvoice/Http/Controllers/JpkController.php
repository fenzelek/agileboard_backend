<?php

namespace App\Modules\SaleInvoice\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Helpers\ErrorCode;
use App\Http\Controllers\Controller;
use App\Models\Other\ModuleType;
use App\Modules\SaleInvoice\Http\Requests\JpkFa;
use App\Modules\SaleInvoice\Services\Jpk\InvoicesSelector;
use App\Modules\SaleInvoice\Services\Jpk\JpkGenerator;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Response;

class JpkController extends Controller
{
    /**
     * Generate JPK-FA file.
     *
     * @param JpkFa $request
     * @param Guard $auth
     * @param InvoicesSelector $invoices_selector
     * @param JpkGenerator $jpk_generator
     *
     * @return \Illuminate\Http\JsonResponse|Response
     */
    public function index(
        JpkFa $request,
        Guard $auth,
        InvoicesSelector $invoices_selector,
        JpkGenerator $jpk_generator
    ) {
        $company = $auth->user()->selectedCompany();

        $jpk_status = $company->appSettings(ModuleType::INVOICES_JPK_EXPORT);

        if (! $jpk_status) {
            return ApiResponse::responseError(
                ErrorCode::SALE_INVOICE_JPK_NOT_ENABLED,
                Response::HTTP_CONFLICT
            );
        }

        if (! $company->jpkDetail) {
            return ApiResponse::responseError(
                ErrorCode::SALE_INVOICE_JPK_DETAILS_MISSING,
                Response::HTTP_CONFLICT
            );
        }

        if ($company->vat_payer === null) {
            return ApiResponse::responseError(
                ErrorCode::SALE_INVOICE_JPK_VAT_PAYER_NOT_FILLED_IN,
                Response::HTTP_CONFLICT
            );
        }

        $invoices = $invoices_selector->get(
            $company,
            $request->input('start_date'),
            $request->input('end_date')
        );

        $jpk_generator->setStartDate($request->input('start_date'))
            ->setEndDate($request->input('end_date'));

        $file_content = $jpk_generator->getFileContent($company, $invoices);

        $headers = [
            'Content-type' => $jpk_generator->getFileContentType(),
            'Content-Disposition' => 'attachment; filename=' . $jpk_generator->getFileName(),
        ];

        return response()->make($file_content, 200, $headers);
    }
}
