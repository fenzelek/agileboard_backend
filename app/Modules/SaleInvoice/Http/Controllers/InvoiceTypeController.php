<?php

namespace App\Modules\SaleInvoice\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Models\Db\InvoiceType;
use App\Models\Other\InvoiceTypeStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceTypeController extends Controller
{
    /**
     * Get list of types of invoices.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $notInRegister = [];
        if ($request->input('register') == 1) {
            $notInRegister = [InvoiceTypeStatus::PROFORMA];
        }

        return ApiResponse::responseOk(InvoiceType::orderBy('id')
            ->whereNotIn('slug', $notInRegister)->get());
    }
}
