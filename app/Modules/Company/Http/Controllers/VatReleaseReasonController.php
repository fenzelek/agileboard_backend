<?php

namespace App\Modules\Company\Http\Controllers;

use App\Models\Db\VatReleaseReason;
use Illuminate\Http\JsonResponse;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;

class VatReleaseReasonController extends Controller
{
    /**
     * Display a list of vat release reasons.
     *
     * @return JsonResponse
     */
    public function index()
    {
        return ApiResponse::responseOk(VatReleaseReason::all());
    }
}
