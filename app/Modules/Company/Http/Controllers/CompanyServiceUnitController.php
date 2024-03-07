<?php

namespace App\Modules\Company\Http\Controllers;

use App\Models\Db\ServiceUnit;
use Illuminate\Http\JsonResponse;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;

class CompanyServiceUnitController extends Controller
{
    /**
     * Display a list of service units.
     *
     * @return JsonResponse
     */
    public function index()
    {
        return ApiResponse::responseOk(ServiceUnit::orderBy('order_number')->get());
    }
}
