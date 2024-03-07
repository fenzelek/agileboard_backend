<?php

namespace App\Modules\SaleInvoice\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Models\Db\InvoiceFormat;
use Illuminate\Http\Response;

class InvoiceFormatController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        return ApiResponse::responseOk(InvoiceFormat::orderBy('id')->get());
    }
}
