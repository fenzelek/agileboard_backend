<?php

namespace App\Modules\Sale\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Db\PaymentMethod;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use App\Modules\Sale\Http\Requests\PaymentMethod as RequestPaymentMethod;

class PaymentMethodsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(RequestPaymentMethod $request)
    {
        if ($request->input('invoice_restrict')) {
            return ApiResponse::responseOk(
                PaymentMethod::where('invoice_restrict', false)
                    ->orderBy('order')->orderBy('id')->get()
            );
        }

        return ApiResponse::responseOk(PaymentMethod::orderBy('order')->orderBy('id')->get());
    }
}
