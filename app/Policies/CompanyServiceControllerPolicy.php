<?php

namespace App\Policies;

use App\Models\Db\CompanyService;
use App\Models\Db\User;

class CompanyServiceControllerPolicy extends BasePolicy
{
    protected $group = 'company-service';

    public function update(User $user = null, $serviceId)
    {
        // user is allowed to update only product for current company that hasn't been used yet
        $company_service = CompanyService::whereHas('company', function ($q) use ($user) {
            $q->where('id', $user->getSelectedCompanyId());
        })->where('is_used', 0)->find($serviceId);

        return $company_service !== null;
    }
}
