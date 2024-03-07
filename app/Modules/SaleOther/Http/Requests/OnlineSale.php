<?php

namespace App\Modules\SaleOther\Http\Requests;

use App\Http\Requests\Request;

class OnlineSale extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'email' => ['email'],
            'date_start' => ['date'],
            'date_end' => ['date'],
            'no_invoice' => ['boolean'],
            'year' => ['integer', 'min:2016', 'max:2050'],
            'month' => ['integer', 'min:1', 'max:12'],
        ];

        if ($this->input('month')) {
            $rules['year'] = array_merge($rules['year'], ['required']);
        }

        return $rules;
    }
}
