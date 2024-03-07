<?php

namespace App\Modules\Company\Http\Requests;

use App\Http\Requests\Request;
use App\Models\Db\Package as PackageModel;
use Illuminate\Validation\Rule;

class Package extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'type' => ['required', Rule::in([PackageModel::PREMIUM])],
        ];
    }
}
