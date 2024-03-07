<?php

declare(strict_types=1);

namespace Tests\Feature\App\Modules\Integration\Http\Controllers\TimeTrackingActivityController\ActivityReport;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Tests\TestCase;

class ActivityReportTest extends TestCase
{
    use DatabaseTransactions;
    use ActivityReportTrait;


    /**
     * @feature Integration
     * @scenario Get activity report
     * @case User is allowed
     *
     * @dataProvider allowedRoleTypeProvider
     * @test
     */
    public function activityReport_WhenUserAllowed_Success(string $role_type): void
    {
        //GIVEN
        $user = $this->createNewUser();
        $company = $this->createCompany();
        $day = Carbon::parse('2022-10-13');
        $this->prepareActivityForUserCompany($user, $company, $role_type, $day);

        //WHEN
        $response = $this
            ->actingAs($user, 'api')
            ->getJson(
                route('time-tracking.report') . '?' . Arr::query([
                    'selected_company_id' => $company->id,
                    'day' => $day->format('Y-m-d'),
                ])
            );

        //THEN
        $response->assertOk();
        $response->assertJsonStructure($this->expectedSuccessStructure());
    }

    /**
     * @feature Integration
     * @scenario Get activity report
     * @case User is not allowed
     *
     * @dataProvider notAllowedRoleTypeProvider
     * @test
     */
    public function activityReport_WhenUserIsNotAllowed_Unauthorized(string $role_type): void
    {
        //GIVEN
        $user = $this->createNewUser();
        $company = $this->createCompany();
        $day = Carbon::parse('2022-10-13');
        $this->prepareActivityForUserCompany($user, $company, $role_type, $day);

        //WHEN
        $response = $this
            ->actingAs($user, 'api')
            ->getJson(
                route('time-tracking.report') . '?' . Arr::query([
                    'selected_company_id' => $company->id,
                    'day' => $day->format('Y-m-d'),
                ])
            );

        //THEN
        $response->assertUnauthorized();
    }
}
