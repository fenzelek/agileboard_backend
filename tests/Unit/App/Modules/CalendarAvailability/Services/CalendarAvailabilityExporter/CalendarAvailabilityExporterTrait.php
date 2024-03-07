<?php

declare(strict_types=1);

namespace Tests\Unit\App\Modules\CalendarAvailability\Services\CalendarAvailabilityExporter;

use App\Models\Db\Company;
use App\Models\Db\Role;
use App\Models\Db\User;
use App\Models\Db\UserAvailability;
use App\Models\Db\UserCompany;
use App\Models\Other\UserAvailabilityStatusType;
use App\Models\Other\UserCompanyStatus;
use Carbon\Carbon;

trait CalendarAvailabilityExporterTrait
{
    protected function createUserWithName(string $first_name, string $last_name): User
    {
        return factory(User::class)->create([
            'first_name' => $first_name,
            'last_name' => $last_name,
        ]);
    }

    protected function createUserAvailability(
        int $user_id,
        int $company_id,
        string $day,
        string $time_start,
        string $time_stop,
        bool $is_overtime,
        bool $is_user_available,
        string $status = UserAvailabilityStatusType::ADDED
    ): UserAvailability {
        return factory(UserAvailability::class)->create([
            'user_id' => $user_id,
            'company_id' => $company_id,
            'day' => Carbon::parse($day),
            'time_start' => $time_start,
            'time_stop' => $time_stop,
            'overtime' => $is_overtime,
            'available' => $is_user_available,
            'status' => $status,
        ]);
    }

    protected function createCompany(array $attributes = []): Company
    {
        return factory(Company::class)->create($attributes);
    }

    protected function addUserToCompany(int $user_id, int $company_id, string $company_role, ?string $department=null): void
    {
        UserCompany::query()->create([
            'company_id' => $company_id,
            'user_id' => $user_id,
            'role_id' => Role::query()->where('name', $company_role)->first()->id,
            'status' => UserCompanyStatus::APPROVED,
            'department' => $department,
        ]);
    }

    protected function createUserAvailabilityInCompany(array $attributes = []): UserAvailability
    {
        return factory(UserAvailability::class)->create($attributes);
    }
}
