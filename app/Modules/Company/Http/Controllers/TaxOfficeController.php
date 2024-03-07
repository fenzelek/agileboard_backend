<?php

namespace App\Modules\Company\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Db\TaxOffice;

class TaxOfficeController
{
    /**
     * Get list of available tax offices.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        return ApiResponse::responseOk(TaxOffice::orderBy('name', 'ASC')->get());
    }
}
