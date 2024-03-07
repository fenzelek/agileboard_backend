<?php

namespace App\Modules\SaleInvoice\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Models\Other\SaleInvoice\FilterOption;
use Illuminate\Http\JsonResponse;

class InvoiceFilterController extends Controller
{
    /**
     * Get list of filter of invoices.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $data = [];

        foreach (FilterOption::all() as $filter) {
            $data [] = [
                'slug' => $filter,
                'description' => FilterOption::translate($filter),
            ];
        }

        return ApiResponse::responseOk($data);
    }
}
