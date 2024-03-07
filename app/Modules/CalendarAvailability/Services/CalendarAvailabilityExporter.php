<?php

declare(strict_types=1);

namespace App\Modules\CalendarAvailability\Services;

use App\Exports\AvailabilityExport;
use App\Models\Db\Role;
use App\Models\Db\User;
use App\Models\Db\UserAvailability;
use App\Models\Other\RoleType;
use App\Modules\CalendarAvailability\Models\AvailabilityExportDto;
use App\Modules\CalendarAvailability\Models\UserWithAvailabilities;
use App\Modules\CalendarAvailability\Models\UserWithoutAvailabilities;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Collection;

class CalendarAvailabilityExporter
{
    private User $user;
    private Guard $auth;
    private Role $role;

    public function __construct(User $user, Guard $auth, Role $role)
    {
        $this->user = $user;
        $this->auth = $auth;
        $this->role = $role;
    }

    public function getExport(Carbon $start_date, Carbon $end_date, ?string $department=null): AvailabilityExport
    {
        return new AvailabilityExport(
            $this->getUsersAvailabilities($start_date, $end_date, $department)
        );
    }

    public function getUsersAvailabilities(
        Carbon $start_date,
        Carbon $end_date,
        ?string $department=null
    ): Collection {


        $users_with_availabilities_dto = $this->getUsersWithAvailabilities($start_date, $end_date, $department)
            ->map(function (UserWithAvailabilities $user){
                return $user->getAvailabilities()
                    ->map(fn(UserAvailability $availability) => new AvailabilityExportDto(
                        (bool) $availability->available,
                        (bool) $availability->overtime,
                        Carbon::parse($availability->day),
                        $availability->time_start,
                        $availability->time_stop,
                        $availability->description,
                        $availability->status,
                        $user->getUserCompany()->department,
                        $user->getUser()->id,
                        $user->getUser()->first_name,
                        $user->getUser()->last_name,
                    ));
                })->flatten();

        $users_without_availabilities_dto = $this->getUsersWithoutAvailabilities($start_date, $end_date, $department)
            ->map(fn(UserWithoutAvailabilities $user) => new AvailabilityExportDto(
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    $user->getUserCompany()->department,
                    $user->getUser()->id,
                    $user->getUser()->first_name,
                    $user->getUser()->last_name,
            ));

        return Collection::make(array_merge(
            $users_with_availabilities_dto->all(),
            $users_without_availabilities_dto->all()
        ));
    }

    /**
     * @param  Carbon  $start_date
     * @param  Carbon  $end_date
     * @param  string|null  $department
     * @return Collection|UserWithAvailabilities[]
     */
    private function getUsersWithAvailabilities(
        Carbon $start_date,
        Carbon $end_date,
        ?string $department
    ): Collection {
        $selected_company_id = $this->auth->user()->getSelectedCompanyId();

        return $this->user->newQuery()
            ->active()
            ->allowed(null, [$this->role->findByName(RoleType::CLIENT)->id])
            ->withSelectedUserCompany($selected_company_id)
            ->when($department, fn($query) => $query->bySelectedCompanyDepartment($selected_company_id, $department))
            ->hasAvailabilities($start_date, $end_date, $selected_company_id)
            ->withAvailabilities($start_date, $end_date, $selected_company_id)
            ->get()
            ->map(fn(User $user) => new UserWithAvailabilities($user, $user->availabilities, $user->userCompanies->first()));
    }

    /**
     * @param  Carbon  $start_date
     * @param  Carbon  $end_date
     * @param  string|null  $department
     * @return Collection|UserWithoutAvailabilities[]
     */
    private function getUsersWithoutAvailabilities(
        Carbon $start_date,
        Carbon $end_date,
        ?string $department
    ): Collection {
        $selected_company_id = $this->auth->user()->getSelectedCompanyId();

        return $this->user->newQuery()
            ->active()
            ->allowed(null, [$this->role->findByName(RoleType::CLIENT)->id])
            ->withSelectedUserCompany($selected_company_id)
            ->doesntHaveAvailabilities($start_date, $end_date, $selected_company_id)
            ->when($department, fn($query) => $query->bySelectedCompanyDepartment($selected_company_id, $department))
            ->get()
            ->map(fn(User $user) => new UserWithoutAvailabilities($user, $user->userCompanies->first()));
    }
}
