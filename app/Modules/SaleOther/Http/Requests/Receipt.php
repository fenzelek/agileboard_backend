<?php

namespace App\Modules\SaleOther\Http\Requests;

use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

class Receipt extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'user_id' => ['integer', 'min:1'],
            'date_start' => ['date'],
            'date_end' => ['date'],
            'payment_method_id' => [
                'integer',
                Rule::exists('payment_methods', 'id'),
            ],
            'no_invoice' => ['boolean'],
            'year' => ['integer', 'min:2016', 'max:2050'],
            'month' => ['integer', 'min:1', 'max:12'],
        ];

        if ($this->input('month')) {
            $rules['year'][] = 'required';
        }

        return $rules;
    }
}
