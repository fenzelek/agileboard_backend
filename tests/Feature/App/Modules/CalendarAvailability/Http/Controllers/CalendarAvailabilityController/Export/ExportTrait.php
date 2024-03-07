<?php

declare(strict_types=1);

namespace Tests\Feature\App\Modules\CalendarAvailability\Http\Controllers\CalendarAvailabilityController\Export;

use App\Models\Db\Company;
use App\Models\Db\User;
use App\Models\Db\UserAvailability;
use App\Models\Db\UserCompany;
use App\Models\Other\DepartmentType;
use App\Models\Other\RoleType;
use App\Models\Other\UserCompanyStatus;
use Carbon\Carbon;

trait ExportTrait
{
    public function authorizedRoleTypeProvider(): array
    {
        return [[RoleType::ADMIN, RoleType::OWNER, RoleType::DEVELOPER]];
    }

    public function unauthorizedRoleTypeProvider(): array
    {
        return [[RoleType::CLIENT]];
    }

    protected function prepareData(string $role_type)
    {
        $this->company = $this->createCompanyWithRole($role_type);
        $users = factory(User::class)->times(2)->create();
        foreach ($users as $user) {
            UserCompany::query()->create([
                'user_id' => $user->id,
                'company_id' => factory(Company::class)->create()->id,
                'status' => UserCompanyStatus::APPROVED,
            ]);
            UserCompany::query()->create([
                'user_id' => $user->id,
                'company_id' => $this->company->id,
                'status' => UserCompanyStatus::APPROVED,
                'department' => null,
            ]);
            $this->createUserAvailability(
                $user->id,
                $this->company->id,
                Carbon::now(),
                Carbon::now()->subHours(2),
                Carbon::now()->addHours(2)
            );
        }
    }

    protected function createUserAvailability(
        int $user_id,
        int $company_id,
        Carbon $day,
        Carbon $time_start,
        Carbon $time_stop
    ) {
        factory(UserAvailability::class)->create([
            'company_id' => $company_id,
            'user_id' => $user_id,
            'day' => $day->toDateString(),
            'time_start' => $time_start->toTimeString(),
            'time_stop' => $time_stop->toTimeString(),
        ]);
    }
}
