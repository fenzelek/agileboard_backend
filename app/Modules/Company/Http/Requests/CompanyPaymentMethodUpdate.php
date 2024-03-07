<?php

namespace App\Modules\Company\Http\Requests;

use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

class CompanyPaymentMethodUpdate extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'default_payment_method_id' => ['required', Rule::exists('payment_methods', 'id')],
        ];
    }
}
