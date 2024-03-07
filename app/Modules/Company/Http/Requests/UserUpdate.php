<?php

namespace App\Modules\Company\Http\Requests;

use App\Http\Requests\Request;
use App\Models\Other\RoleType;
use App\Models\Db\Role;
use App\Models\Other\UserCompanyStatus;
use Illuminate\Validation\Rule;

class UserUpdate extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $selected_company_id = auth()->user()->getSelectedCompanyId();
        $company_roles = Role::whereHas('companies', function ($q) use ($selected_company_id) {
            $q->where('id', $selected_company_id);
        })->where('name', '<>', RoleType::OWNER)->pluck('id')->all();

        return [
            'user_id' => [
                'required',
                Rule::exists('user_company', 'user_id')
                    ->where('company_id', $selected_company_id)
                    ->where('status', UserCompanyStatus::APPROVED)
                    ->whereNot('role_id', Role::findByName(RoleType::OWNER)->id),
            ],
            'role_id' => [
                'required',
                Rule::in($company_roles),
            ],
        ];
    }
}
