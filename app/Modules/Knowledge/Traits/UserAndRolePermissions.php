<?php

namespace App\Modules\Knowledge\Traits;

use Illuminate\Validation\Rule;

trait UserAndRolePermissions
{
    protected function getRules()
    {
        $selected_company_id = auth()->user()->getSelectedCompanyId();

        return [
            'users' => [
                'nullable',
                'array',
            ],
            'users.*' => [
                'numeric',
                Rule::exists('project_user', 'user_id')
                    ->where('project_id', $this->route('project')->id),
            ],
            'roles' => [
                'nullable',
                'array',
            ],
            'roles.*' => [
                'numeric',
                Rule::exists('company_role', 'role_id')
                    ->where('company_id', $selected_company_id),
            ],
        ];
    }
}
