<?php

declare(strict_types=1);

namespace Tests\Unit\App\Modules\CalendarAvailability\Services\CalendarAvailabilityExporter;

use App\Models\Db\Company;
use App\Models\Db\User;
use App\Models\Other\DepartmentType;
use App\Models\Other\RoleType;
use App\Models\Other\UserAvailabilityStatusType;
use App\Modules\CalendarAvailability\Models\AvailabilityExportDto;
use App\Modules\CalendarAvailability\Services\CalendarAvailabilityExporter;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CalendarAvailabilityExporterTest extends TestCase
{
    use DatabaseTransactions;
    use CalendarAvailabilityExporterTrait;

    private CalendarAvailabilityExporter $service;
    private Company $selected_company;

    /**
     * @test
     */
    public function getUserAvailabilities_ReturnCorrectAvailability(
        string $user_first_name = 'Pablo',
        string $user_last_name = 'Nowak',
        ?string $department = null,
        string $day = '2023-10-12',
        ?string $time_start = '11:00:00',
        ?string $time_stop = '18:00:00',
        bool $is_overtime = false,
        bool $is_user_available = true,
        string $status = UserAvailabilityStatusType::ADDED,
        string $filter_start_date='2023-10-11',
        string $filter_end_date='2023-10-16'
    ): void {
        //Given
        $user = $this->createUserWithName($user_first_name, $user_last_name);
        $this->addUserToCompany($user->id, $this->selected_company->id, RoleType::DEVELOPER, $department);
        $this->createUserAvailability(
            $user->id,
            $this->selected_company->id,
            $day,
            $time_start,
            $time_stop,
            $is_overtime,
            $is_user_available,
            $status
        );

        //When
        $result = $this->service->getUsersAvailabilities(
            Carbon::parse($filter_start_date),
            Carbon::parse($filter_end_date),
        );

        //Then
        $this->assertCount(2, $result);
        /** @var AvailabilityExportDto $availability */
        $availability = $result->filter(function (AvailabilityExportDto $dto) use ($user) {
            return $dto->getUserId() === $user->id;
        })->first();
        $this->assertSame($department, $availability->getDepartment());
        $this->assertSame($user_first_name, $availability->getUserFirstName());
        $this->assertSame($user_last_name, $availability->getUserLastName());
        $this->assertSame($is_overtime, $availability->getIsOvertime());
        $this->assertSame($is_user_available, $availability->getIsUserAvailable());
        $this->assertSame($status, $availability->getStatus());
        $this->assertSame($time_start, $availability->getTimeStart());
        $this->assertSame($time_stop, $availability->getTimeStop());
        $this->assertSame($day, $availability->getDay()->toDateString());
    }

    /**
     * @test
     */
    public function getUserAvailabilities_WhenUserDoesNotHaveAvailabilities_ShouldReturnOneEmptyRecord(): void
    {
        //Given
        $user_first_name = 'Maciek';
        $user_last_name = 'Kowalski';
        $department = DepartmentType::DEVELOPERS;
        $user = $this->createUserWithName($user_first_name, $user_last_name);
        $this->addUserToCompany($user->id, $this->selected_company->id, RoleType::DEVELOPER, $department);

        //When
        $result = $this->service->getUsersAvailabilities(
            Carbon::now(),
            Carbon::now(),
        );

        //Then
        $this->assertCount(2, $result);
        /** @var AvailabilityExportDto $availability */
        $availability = $result->filter(function (AvailabilityExportDto $dto) use ($user) {
            return $dto->getUserId() === $user->id;
        })->first();
        $this->assertSame($department, $availability->getDepartment());
        $this->assertSame($user_first_name, $availability->getUserFirstName());
        $this->assertSame($user_last_name, $availability->getUserLastName());
        $this->assertNull($availability->getIsOvertime());
        $this->assertNull($availability->getIsUserAvailable());
        $this->assertNull($availability->getStatus());
        $this->assertNull($availability->getTimeStart());
        $this->assertNull($availability->getTimeStop());
        $this->assertNull($availability->getDay());
    }

    /**
     * @test
     */
    public function getUserAvailabilities_WhenUserHasTwoCompanies_ShouldReturnDepartmentFromUserSelectedCompany(): void
    {
        //Given
        Carbon::setTestNow(Carbon::parse('2023-10-12 09:00'));
        $expected_department = DepartmentType::DEVELOPERS;
        $user = factory(User::class)->create();
        $this->addUserToCompany($user->id, $this->selected_company->id, RoleType::DEVELOPER, $expected_department);
        $this->addUserToCompany($user->id, $this->createCompany()->id, RoleType::DEVELOPER, 'Tele');
        $this->createUserAvailabilityInCompany([
            'user_id' => $user->id,
            'company_id' => $this->selected_company->id,
            'day' => Carbon::now(),
            'time_start' => '12:00',
            'time_stop' => '16:00',
        ]);

        //When
        $result = $this->service->getUsersAvailabilities(
            Carbon::now()->subDay(),
            Carbon::now()->addDays(5)
        );

        //Then
        $this->assertCount(2, $result);
        /** @var AvailabilityExportDto $availability */
        $availability = $result->filter(function (AvailabilityExportDto $dto) use ($user) {
            return $dto->getUserId() === $user->id;
        })->first();
        $this->assertSame($expected_department, $availability->getDepartment());
    }

    /**
     * @test
     */
    public function getUserAvailabilities_WhenDepartmentFilterPresent_ShouldReturnOnlyUsersFromSelectedCompanyDepartment(): void
    {
        Carbon::setTestNow(Carbon::parse('2023-10-12 09:00'));
        $department = DepartmentType::TELECOMMUNICATION;
        $user_in_department = factory(User::class)->create();
        $user_not_in_department = factory(User::class)->create();
        $this->addUserToCompany($user_in_department->id, $this->selected_company->id, RoleType::DEVELOPER, $department);
        $this->addUserToCompany($user_not_in_department->id, $this->selected_company->id, RoleType::DEVELOPER, null);
        $this->createUserAvailabilityInCompany([
            'user_id' => $user_in_department->id,
            'company_id' => $this->selected_company->id,
            'day' => Carbon::now(),
            'time_start' => '12:00',
            'time_stop' => '16:00',
        ]);
        $this->createUserAvailabilityInCompany([
            'user_id' => $user_not_in_department->id,
            'company_id' => $this->selected_company->id,
            'day' => Carbon::now(),
            'time_start' => '12:00',
            'time_stop' => '16:00',
        ]);

        //When
        $result = $this->service->getUsersAvailabilities(
            Carbon::now()->subDay(),
            Carbon::now()->addDays(5),
            $department
        );

        //Then
        $this->assertCount(1, $result);
        /** @var AvailabilityExportDto $availability */
        $availability = $result->first();
        $this->assertSame($department, $availability->getDepartment());
        $this->assertSame($user_in_department->first_name, $availability->getUserFirstName());
        $this->assertSame($user_in_department->last_name, $availability->getUserLastName());
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(CalendarAvailabilityExporter::class);
        $this->createUser();
        $guard = $this->app->make(Guard::class);
        $guard->setUser($this->user);
        $this->selected_company = $this->createCompany();
        $this->addUserToCompany($this->user->id, $this->selected_company->id, RoleType::ADMIN);
        $this->user->setSelectedCompany($this->selected_company->id);
    }
}
