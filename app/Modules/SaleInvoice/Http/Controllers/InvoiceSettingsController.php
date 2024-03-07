<?php

namespace App\Modules\SaleInvoice\Http\Controllers;

use App\Helpers\ErrorCode;
use App\Modules\SaleInvoice\Services\InvoiceSettings as InvoiceSettingsService;
use DB;
use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Models\Db\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Auth\Guard;
use App\Modules\SaleInvoice\Http\Requests\InvoiceSettings;

class InvoiceSettingsController extends Controller
{
    /**
     * Display a current invoice format for company.
     *
     * @param Guard $auth
     *
     * @return JsonResponse
     */
    public function show(Guard $auth)
    {
        $company = Company::find($auth->user()->getSelectedCompanyId());

        return ApiResponse::responseOk([
            'default_payment_term_days' => $company->default_payment_term_days,
            'invoice_registries' => ['data' => $company->registries],
            'default_invoice_gross_counted' => $company->default_invoice_gross_counted,
            'vat_payer' => $company->vat_payer,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param InvoiceSettings $request
     * @param Guard $auth
     * @param InvoiceSettingsService $service
     *
     * @return JsonResponse
     */
    public function update(InvoiceSettings $request, Guard $auth, InvoiceSettingsService $service)
    {
        $selected_company_id = $auth->user()->getSelectedCompanyId();
        /** @var Company $company */
        $company = Company::findOrFail($selected_company_id);

        if ($service->blockedGrossCountedSetting($company, $request)) {
            return ApiResponse::responseError(ErrorCode::COMPANY_BLOCKED_CHANGING_GROSS_COUNTED_SETTING, 421);
        }

        DB::transaction(function () use ($request, $company, $auth, $service) {
            $default_parameters = [
                'default_payment_term_days' => $request->input('default_payment_term_days'),
                'default_invoice_gross_counted' => $request->input('default_invoice_gross_counted'),
            ];

            $company->update($default_parameters);
            $service->updateRegistries($request, $company, $auth->user());
        });

        return ApiResponse::responseOk([], 200);
    }
}
