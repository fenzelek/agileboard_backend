<?php

namespace Tests\Feature\App\Modules\CalendarAvailability\Http\Controllers\CalendarDayOffController\Destroy;

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
     * @scenario Add Vacation
     * @case Vacation add successful
     * @test
     */
    public function daysOff_removeSuccessful()
    {
        //GIVEN
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);
        $date = '2010-01-01';
        $this->createDayOff($date, UserAvailabilitySourceType::EXTERNAL, $this->user->id, $company->id);
        //WHEN


        $entry_data = [
            'user_id' => $this->user->id,
            'days' => [$date],
            'selected_company_id' => $company->id,
        ];

        //WHEN
        list($token, $api_token) = $this->createCompanyTokenForUser(
            $this->user,
            $company,
            RoleType::API_COMPANY
        );

        $response = $this->json(
            'DELETE',
            route('calendar.days-off.add'),
            $entry_data,
            ['Authorization-Api-Token' => $api_token]
        );

        //THEN
        $response->assertSuccessful();
        $this->assertSame(1, Arr::get($response->assertSuccessful(), 'data.deleted'));
    }

    /**
     * @feature Calendar
     * @scenario Add Vacation
     * @case Vacation add failed
     * @test
     */
    public function daysOff_removeFailed()
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
            'DELETE',
            route('calendar.days-off.add', ['selected_company_id' => $company->id,]),
            [],
            ['Authorization-Api-Token' => $api_token]
        );

        //THEN
        $this->verifyResponseValidation($response, [
            'user_id',
            'days'
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
