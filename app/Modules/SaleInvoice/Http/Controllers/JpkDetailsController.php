<?php

namespace App\Modules\SaleInvoice\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Db\CompanyJpkDetail;
use App\Modules\SaleInvoice\Http\Requests\JpkDetails;
use Illuminate\Contracts\Auth\Guard;

class JpkDetailsController extends Controller
{
    /**
     * Get JPK details for current company.
     *
     * @param Guard $auth
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Guard $auth)
    {
        $jpk_details = CompanyJpkDetail::companyId($auth->user()->getSelectedCompanyId())
            ->with('taxOffice')->firstOrFail();

        return ApiResponse::responseOk($jpk_details);
    }

    /**
     * Update JPK details for current company.
     *
     * @param JpkDetails $request
     * @param Guard $auth
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(JpkDetails $request, Guard $auth)
    {
        $company_id = $auth->user()->getSelectedCompanyId();
        $jpk_details = CompanyJpkDetail::firstOrCreate(['company_id' => $company_id]);

        $jpk_details->update($request->validated());

        return ApiResponse::responseOk($jpk_details->fresh()->load('taxOffice'));
    }
}
