<?php

namespace App\Modules\SaleInvoice\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use App\Models\Db\InvoiceReverseCharge;

class InvoiceReverseChargeController extends Controller
{
    /**
     * Display a listing of the margin procedures.
     *
     * @return JsonResponse
     */
    public function index()
    {
        return ApiResponse::responseOk(InvoiceReverseCharge::orderBy('id')->get());
    }
}
