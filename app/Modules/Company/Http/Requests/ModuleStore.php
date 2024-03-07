<?php

namespace App\Modules\Company\Http\Requests;

use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

class ModuleStore extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'days' => ['required', 'in:0,30,365'],
            'is_test' => ['required', 'in:0,1'],
            'currency' => ['required', 'in:PLN,EUR'],
            'mod_price_id' => [
                'required',
                Rule::exists('mod_prices', 'id')
                    ->where('currency', $this->input('currency', 'DEFAULT'))
                    ->where(function ($q) {
                        if ($this->input('days')) {
                            $q->where('days', $this->input('days'));
                        }
                    }),
            ],
            'checksum' => ['required', 'string'],
        ];
    }
}
