<?php

namespace App\Modules\SaleInvoice\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Models\Db\InvoiceMarginProcedure;
use Illuminate\Http\JsonResponse;

class InvoiceMarginProcedureController extends Controller
{
    /**
     * Display a listing of the margin procedures.
     *
     * @return JsonResponse
     */
    public function index()
    {
        return ApiResponse::responseOk(InvoiceMarginProcedure::orderBy('id')->get());
    }
}
