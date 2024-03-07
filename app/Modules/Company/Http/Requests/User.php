<?php

namespace App\Modules\Company\Http\Requests;

use App\Http\Requests\Request;
use App\Models\Other\UserCompanyStatus;
use Illuminate\Validation\Rule;

class User extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'selected_company_id' => ['required', 'max:255'],
            'company_status' => [Rule::in(UserCompanyStatus::all())],
            'search' => ['nullable', 'string'],
        ];
    }
}
