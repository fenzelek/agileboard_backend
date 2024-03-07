<?php

namespace App\Modules\SaleOther\Http\Requests;

use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

class ErrorLogIndex extends Request
{
    public function rules()
    {
        /*
         * Get the validation rules that apply to the request.
         *
         * @return array
         */
        return [
            'user_id' => [
                'integer',
                Rule::exists('user_company', 'user_id')
                    ->where('company_id', $this->input('selected_company_id')),
            ],
            'request' => [
                'array',
            ],
            'url' => [
                'string',
                'in:receipts,online_sale',
            ],
        ];
    }
}
