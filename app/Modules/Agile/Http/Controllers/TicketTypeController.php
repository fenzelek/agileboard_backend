<?php

namespace App\Modules\Agile\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Db\TicketType;

class TicketTypeController extends Controller
{
    /**
     * Display list of ticket types.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        return ApiResponse::responseOk(TicketType::orderBy('id')->get());
    }
}
