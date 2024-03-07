<?php

namespace App\Modules\Contractor\Http\Requests;

use App\Http\Requests\Request;

class ContractorIndex extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'search' => 'max:255',
        ];
    }
}
