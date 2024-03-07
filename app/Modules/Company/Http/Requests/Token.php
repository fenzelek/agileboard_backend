<?php

namespace App\Modules\Company\Http\Requests;

use App\Http\Requests\Request;
use App\Models\Db\Role;
use App\Models\Other\RoleType;
use App\Models\Other\UserCompanyStatus;
use Illuminate\Validation\Rule;

class Token extends Request
{
    public function rules()
    {
        $company_id = $this->container['auth']->user()->getSelectedCompanyId();
        $api_roles = Role::whereIn('name', [RoleType::API_USER, RoleType::API_COMPANY])
            ->pluck('id')->all();

        return [
            'user_id' => ['required', $this->assignedToCompany($company_id)],
            'role_id' => ['required', Rule::in($api_roles)],
            'domain' => ['present', 'max:255'],
            'ip_from' => ['present', 'required_with:ip_to', 'max:15'],
            'ip_to' => ['present', 'max:15'],
            'ttl' => ['required', 'integer', 'min:1', 'max:1440'],
        ];
    }

    protected function assignedToCompany($company_id)
    {
        return Rule::exists('user_company', 'user_id')
            ->where('company_id', $company_id)
            ->where('status', UserCompanyStatus::APPROVED);
    }
}
