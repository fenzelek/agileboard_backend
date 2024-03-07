<?php

declare(strict_types=1);

namespace Tests\Unit\App\Modules\Integration\Services\ActivityReport;

use App\Models\Db\Ticket;
use App\Modules\Integration\Models\ActivityReportDto;
use App\Modules\Integration\Services\ActivityReport;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ActivityReportTest extends TestCase
{
    use DatabaseTransactions;
    use ActivityReportTrait;

    private ActivityReport $service;

    /**
     * @feature Integration
     * @scenario Get activity report
     * @case User provide availabilities
     *
     * @test
     */
    public function report_WhenUserProvideAvailabilities_ShouldReturnThatUserIsNotAvailable(): void
    {
        //GIVEN
        $data = $this->prepareUserProvideAvailabilitiesData();

        $day = $data['day'];
        $company_id = $data['company_id'];
        $is_available = $data['is_available'];
        $availability_seconds = $data['availability_seconds'];
        $work_progress = $data['work_progress'];

        //WHEN
        $result = $this->service->report($day, $company_id)->first();

        //THEN
        $this->assertInstanceOf(ActivityReportDto::class, $result);
        $this->assertTrue($result->getIsAvailable());
        $this->assertSame($availability_seconds, $result->getAvailabilitySeconds());
        $this->assertSame($work_progress, $result->getWorkProgress());
    }

    /**
     * @feature Integration
     * @scenario Get activity report
     * @case User did not provide availabilities
     *
     * @test
     */
    public function report_WhenUserDidNotProvideAvailabilities_ShouldReturnThatUserIsNotAvailable(): void
    {
        //GIVEN
        $day = Carbon::parse('2022-10-12');
        $user = $this->createNewUser();
        $company = $this->createCompany();
        $this->addUserToCompany($user, $company);

        //WHEN
        $result = $this->service->report($day, $company->id)->first();

        //THEN
        $this->assertInstanceOf(ActivityReportDto::class, $result);
        $this->assertFalse($result->getIsAvailable());
        $this->assertNull($result->getAvailabilitySeconds());
        $this->assertNull($result->getWorkProgress());
    }

    /**
     * @feature Integration
     * @scenario Get activity report
     * @case User has tracking activities in other company
     *
     * @test
     */
    public function report_WhenUserHasTrackingActivitiesInOtherCompany_ShouldReturnActivitiesFromSelectedCompany(): void
    {
        //GIVEN
        $data = $this->prepareUserHasTrackingActivitiesInOtherCompanyData();

        $day = $data['day'];
        $company_id = $data['company_id'];
        $expected_manual_activities = $data['expected_manual_activities'];
        $expected_tracking_activities = $data['expected_tracking_activities'];
        $expected_manual_activity_tickets = $data['expected_manual_activity_tickets'];

        //WHEN
        $result = $this->service->report($day, $company_id)->first();

        //THEN
        $this->assertInstanceOf(ActivityReportDto::class, $result);
        $this->assertSame($expected_tracking_activities, $result->getSumTrackingActivities());
        $this->assertSame($expected_manual_activities, $result->getSumManualActivities());
        $this->assertSame(2, count($result->getManualTickets()));
        $this->assertManualTicketActivityCorrect($expected_manual_activity_tickets[0], $result->getManualTickets()[0]);
        $this->assertManualTicketActivityCorrect($expected_manual_activity_tickets[1], $result->getManualTickets()[1]);
    }

    /**
     * @feature Integration
     * @scenario Get activity report
     * @case User has activities from other day
     *
     * @test
     */
    public function report_WhenUserHasActivitiesFromOtherDay_ShouldReturnActivitiesFromGivenDay()
    {
        //GIVEN
        $data = $this->prepareUserHasActivitiesFromOtherDayData();

        $day = $data['day'];
        $company_id = $data['company_id'];
        $expected_manual_activities = $data['expected_manual_activities'];
        $expected_tracking_activities = $data['expected_tracking_activities'];
        $expected_manual_activity_tickets = $data['expected_manual_activity_tickets'];

        //WHEN
        $result = $this->service->report($day, $company_id)->first();

        //THEN
        $this->assertInstanceOf(ActivityReportDto::class, $result);
        $this->assertSame($expected_tracking_activities, $result->getSumTrackingActivities());
        $this->assertSame($expected_manual_activities, $result->getSumManualActivities());
        $this->assertSame(1, count($result->getManualTickets()));
        $this->assertManualTicketActivityCorrect($expected_manual_activity_tickets[0], $result->getManualTickets()[0]);
    }

    /**
     * @feature Integration
     * @scenario Get activity report
     * @case Other user has activities
     *
     * @test
     */
    public function report_WhenOtherUserHasActivities_ShouldReturnActivitiesForCorrectUser()
    {
        //GIVEN
        $data = $this->prepareOtherUserHasActivitiesData();

        $user_id = $data['user_id'];
        $day = $data['day'];
        $company_id = $data['company_id'];
        $expected_manual_activities = $data['expected_manual_activities'];
        $expected_tracking_activities = $data['expected_tracking_activities'];
        $expected_manual_activity_tickets = $data['expected_manual_activity_tickets'];

        //WHEN
        $result = $this->service->report($day, $company_id);

        /** @var ActivityReportDto $user_result */
        $user_result = $result->filter(function (ActivityReportDto $dto) use ($user_id) {
            return $dto->getUserId() === $user_id;
        })->first();

        //THEN
        $this->assertCount(2, $result);
        $this->assertSame($expected_tracking_activities, $user_result->getSumTrackingActivities());
        $this->assertSame($expected_manual_activities, $user_result->getSumManualActivities());
        $this->assertSame(1, count($user_result->getManualTickets()));
        $this->assertManualTicketActivityCorrect($expected_manual_activity_tickets[0], $user_result->getManualTickets()[0]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Ticket::unsetEventDispatcher();
        $this->service = $this->app->make(ActivityReport::class);
    }
}
