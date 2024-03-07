<?php

namespace Tests\Feature\App\Modules\CalendarAvailability\Http\Controllers\CalendarDayOffController\Add;

use App\Models\Db\Company;
use App\Models\Db\Package;
use App\Models\Other\UserCompanyStatus;
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
    public function daysOff_addSuccessful()
    {
        //GIVEN
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);
        auth()->loginUsingId($this->user->id);

        $entry_data = [
            'user_id' => $this->user->id,
            'days' => [
                [
                    'date' => Carbon::tomorrow()->format('Y-m-d'),
                    'description' => 'day off'
                ]
            ],
            'selected_company_id' => $company->id,
        ];

        //WHEN
        list($token, $api_token) = $this->createCompanyTokenForUser(
            $this->user,
            $company,
            RoleType::API_COMPANY
        );

        $response = $this->json(
            'POST',
            route('calendar.days-off.add'),
            $entry_data,
            ['Authorization-Api-Token' => $api_token]
        );

        //THEN
        $response->assertCreated();
        $this->assertTrue(Arr::get($response->assertSuccessful(), 'data.added'));

    }

    /**
     * @feature Calendar
     * @scenario Add Vacation
     * @case Vacation add failed
     * @test
     */
    public function daysOff_addSuccessFailed()
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
            'POST',
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

}
