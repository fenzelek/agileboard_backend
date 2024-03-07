<?php

namespace App\Modules\Company\Http\Requests;

use App\Http\Requests\Request;
use App\Modules\Company\Traits\VatPayerRules;

class Company extends Request
{
    use VatPayerRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = ['name' => ['required', 'max:255']];

        return array_merge($rules, $this->vatPayerCommonRules());
    }
}
