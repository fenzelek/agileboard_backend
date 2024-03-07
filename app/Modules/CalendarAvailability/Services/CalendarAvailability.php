<?php

namespace App\Modules\CalendarAvailability\Services;

use App\Models\Db\Role;
use App\Models\Db\User;
use App\Models\Other\RoleType;
use Carbon\Carbon;
use App\Modules\CalendarAvailability\Contracts\CalendarAvailability as CalendarAvailabilityContract;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Collection;

class CalendarAvailability implements CalendarAvailabilityContract
{
    /**
     * @var User
     */
    protected $user;

    /**
     * @var Guard
     */
    protected $auth;

    /**
     * @var Role
     */
    protected $role;

    protected int $amount_free_days;

    protected CalendarAvailabilitySorter $sorting_service;

    /**
     * CalendarAvailability constructor.
     *
     * @param User $user
     * @param Guard $auth
     * @param Role $role
     */
    public function __construct(User $user, Guard $auth, Role $role, CalendarAvailabilitySorter $sorting_service)
    {
        $this->user = $user;
        $this->auth = $auth;
        $this->role = $role;
        $this->sorting_service = $sorting_service;
    }

    public function find(Carbon $startDate, Carbon $endDate, array $sorts=[], ?string $department=null): Collection
    {
        $selected_company_id = $this->auth->user()->getSelectedCompanyId();

        return $this->user->newQuery()
            ->active()
            ->allowed(null, [$this->role->findByName(RoleType::CLIENT)->id])
            ->orderBy('last_name')
            ->withAvailabilities($startDate, $endDate, $selected_company_id)
            ->when($department!==null, function ($query) use ($selected_company_id, $department) {
                $query->bySelectedCompanyDepartment($selected_company_id, $department);
            })
            ->get();
    }

    public function findByIds(Carbon $startDate, Carbon $endDate, array $users_ids, array $sorts=[]): Collection
    {
        $selected_company_id = $this->auth->user()->getSelectedCompanyId();

        return $this->user->newQuery()
            ->active()
            ->byIds($users_ids)
            ->allowed(null, [$this->role->findByName(RoleType::CLIENT)->id])
            ->orderBy('last_name')
            ->withAvailabilities($startDate, $endDate, $selected_company_id)
            ->get();
    }

    /**
     * @return array
     */
    public function prepareDataToReport($users_availabilities)
    {
        $reports = [];
        foreach ($users_availabilities as $user_availabilities) {
            if ($this->hasAvailabilities($user_availabilities)) {
                continue;
            }

            $this->amount_free_days = 0;
            $builder = new ReportBuilder();

            foreach ($user_availabilities->availabilities as $availability) {
                $month = date('n', strtotime($availability->day));

                $builder->getTimestamp($month, $availability);
                $builder->getOvertime($month, $availability);
                $this->amount_free_days =
                    $builder->getFreeDays($month, $availability, $this->amount_free_days);
            }

            $reports[] = [
                'user_id' => $user_availabilities->id,
                'first_name' => $user_availabilities->first_name,
                'last_name' => $user_availabilities->last_name,
                'months' => $builder->getMonths(),
                'amount_free_days' => $this->amount_free_days,
            ];
        }

        return $reports;
    }

    /**
     * @param Collection|User[] $users
     * @return Collection|User[]
     */
    public function setSelectedCompany(Collection $users): Collection
    {
        return $users->map(function (User $user) {
            $user->setRelation('selected_company', $user->userCompanies->first());
            unset($user->userCompanies);

            return $user;
        });
    }

    /**
     * @param $user_availabilities
     * @return bool
     */
    private function hasAvailabilities($user_availabilities): bool
    {
        return ! count($user_availabilities->availabilities);
    }
}
