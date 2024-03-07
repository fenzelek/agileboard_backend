<?php

namespace App\Modules\Gantt\Services;

use App\Models\Db\Role;
use App\Models\Db\User;
use App\Models\Other\UserCompanyStatus;
use App\Models\CustomCollections\UsersCollection;

class WorkloadUser extends User
{
    public $project_ids;
    public $user;
    public $workloads;
    protected $table = 'users';

    /**
     * @param int $company_id
     * @return UsersCollection
     */
    public function find(int $company_id)
    {
        return self::active()
            ->required($company_id)
            ->assignedProjects($company_id)
            ->select(['id', 'email', 'first_name', 'last_name', 'avatar'])
            ->get();
    }

    /**
     * Scope.
     *
     * @param $query
     * @param $company_id
     * @return mixed
     */
    public function scopeAssignedProjects($query, $company_id)
    {
        return $query->with(['projects' => function ($query) use ($company_id) {
            $query->forCompany($company_id)->open()->select('projects.id');
        }]);
    }

    /**
     * Scope.
     *
     * @param $query
     * @param $company_id
     * @return mixed
     */
    public function scopeRequired($query, $company_id)
    {
        $expected_roles = Role::forGantt()->pluck('id')->toArray();

        return $query->whereHas('userCompanies', function ($query) use ($company_id, $expected_roles) {
            $query->where('company_id', $company_id)
                ->where('status', UserCompanyStatus::APPROVED)
                ->whereIn('role_id', $expected_roles);
        });
    }
}
