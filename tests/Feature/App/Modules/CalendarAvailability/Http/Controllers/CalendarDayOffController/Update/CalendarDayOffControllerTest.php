<?php

namespace Tests\Feature\App\Modules\CalendarAvailability\Http\Controllers\CalendarDayOffController\Update;

use App\Models\Db\Company;
use App\Models\Db\Package;
use App\Models\Other\UserCompanyStatus;
use App\Modules\CalendarAvailability\Models\UserAvailabilitySourceType;
use Carbon\Carbon;
use App\Models\Db\User;
use App\Models\Db\Project;
use App\Models\Other\RoleType;
use App\Helpers\ErrorCode;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\App\Modules\CalendarAvailability\Http\Controllers\CalendarDayOffController\Add\CalendarDayOffControllerTrait;
use Tests\Helpers\CompanyTokenCreator;
use Tests\TestCase;

class CalendarDayOffControllerTest extends TestCase
{
    use DatabaseTransactions;
    use CalendarDayOffControllerTrait;
    use CompanyTokenCreator;

    /**
     * @feature Calendar
     * @scenario update Vacation
     * @case Vacation updated successful
     * @test
     */
    public function daysOff_updateSuccessful()
    {
        //GIVEN
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        $this->createDayOff(Carbon::yesterday()->format('Y-m-d'), UserAvailabilitySourceType::EXTERNAL,  $this->user->id, $company->id);

        auth()->loginUsingId($this->user->id);

        $entry_data = [
            'user_id' => $this->user->id,
            'added_days' => [
                [
                    'date' => Carbon::tomorrow()->format('Y-m-d'),
                    'description' => 'day off'
                ]
            ],
            'deleted_days' => [Carbon::yesterday()->format('Y-m-d'),],
            'selected_company_id' => $company->id,
        ];

        //WHEN
        list($token, $api_token) = $this->createCompanyTokenForUser(
            $this->user,
            $company,
            RoleType::API_COMPANY
        );

        $response = $this->json(
            'PUT',
            route('calendar.days-off.update'),
            $entry_data,
            ['Authorization-Api-Token' => $api_token]
        );

        //THEN
        $response->assertSuccessful();
        $this->assertSame(true, Arr::get($response->assertSuccessful(), 'data.added'));
        $this->assertSame(1, Arr::get($response->assertSuccessful(), 'data.deleted'));

    }

    /**
     * @feature Calendar
     * @scenario Add Vacation
     * @case Vacation updated failed
     * @test
     */
    public function daysOff_updateFailed()
    {
        //GIVEN
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        //WHEN
        list($token, $api_token) = $this->createCompanyTokenForUser(
            $this->user,
            $company,
            RoleType::API_COMPANY
        );

        $response = $this->json(
            'PUT',
            route('calendar.days-off.update', ['selected_company_id' => $company->id,]),
            [],
            ['Authorization-Api-Token' => $api_token]
        );

        //THEN
        $this->verifyResponseValidation($response, [
            'user_id',
            'added_days',
            'deleted_days',
        ]);
    }

    private function createDayOff(string $date, string $source, int $user_id, int $company_id)
    {
        \DB::table('user_availability')->insert([
            [
                'time_start' => '08:00:00',
                'time_stop' => '10:00:00',
                'available' => 0,
                'overtime' => 0,
                'description' => '',
                'status' => 'CONFIRMED',
                'user_id' => $user_id,
                'day' => $date,
                'company_id' => $company_id,
                'source' => $source
            ]
        ]);
    }


}
