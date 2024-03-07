<?php

namespace App\Modules\Sale\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Db\VatRate;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class VatRateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        return ApiResponse::responseOk(VatRate::orderBy('id')->get());
    }
}
